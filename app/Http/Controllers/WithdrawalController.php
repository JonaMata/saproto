<?php

namespace Proto\Http\Controllers;

use Illuminate\Http\Request;

use Proto\Http\Requests;
use Proto\Http\Controllers\Controller;
use Proto\Mail\OmnomcomWithdrawalNotification;
use Proto\Models\Bank;
use Proto\Models\OrderLine;
use Proto\Models\User;
use Proto\Models\Withdrawal;
use Proto\Models\Account;

use Redirect;
use Response;
use Mail;
use Auth;
use Carbon\Carbon;
use DB;

class WithdrawalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("omnomcom.withdrawals.index", ['withdrawals' => Withdrawal::orderBy('id', 'desc')->get()]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("omnomcom.withdrawals.create");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $max = ($request->has('max') ? $request->input('max') : null);
        if ($max <= 0) {
            $max = null;
        }

        $date = strtotime($request->input('date'));
        if ($date === false) {
            $request->session()->flash('flash_message', 'Invalid date.');
            return Redirect::back();
        }

        $withdrawal = Withdrawal::create([
            'date' => date('Y-m-d', $date)
        ]);

        $totalPerUser = [];
        foreach (OrderLine::whereNull('payed_with_withdrawal')->get() as $orderline) {

            if ($orderline->isPayed()) continue;
            if ($orderline->user->bank == null) continue;

            if ($max != null) {
                if (!array_key_exists($orderline->user->id, $totalPerUser)) {
                    $totalPerUser[$orderline->user->id] = 0;
                }

                if ($totalPerUser[$orderline->user->id] + $orderline->total_price > $max) continue;
            }

            $orderline->withdrawal()->associate($withdrawal);
            $orderline->save();

            if ($max != null) {
                $totalPerUser[$orderline->user->id] += $orderline->total_price;
            }

        }

        return Redirect::route('omnomcom::withdrawal::show', ['id' => $withdrawal->id]);

    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return view("omnomcom.withdrawals.show", ['withdrawal' => Withdrawal::findOrFail($id)]);
    }

    /**
     * Display the accounts associated with the withdrawal.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function showAccounts($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        // We do one massive query to reduce the number of queries.
        $orderlines = DB::table('orderlines')
            ->join('products', 'orderlines.product_id', '=', 'products.id')
            ->join('accounts', 'products.account_id', '=', 'accounts.id')
            ->select('orderlines.*', 'accounts.account_number', 'accounts.name')
            ->where('orderlines.payed_with_withdrawal', $withdrawal->id)
            ->get();

        $accounts = [];

        foreach ($orderlines as $orderline) {
            // We sort by date, where a date goes from 6am - 6am.
            $sortDate = Carbon::parse($orderline->created_at)->subHours(6)->toDateString();

            // Shorthand variable names.
            $accnr = $orderline->account_number;

            // Add account to dataset if not existing yet.
            if (!isset($accounts[$accnr])) {
                $accounts[$accnr] = (object)[
                    'byDate' => [],
                    'name' => $orderline->name,
                    'total' => 0
                ];
            }

            // Add orderline to total account price.
            $accounts[$accnr]->total += $orderline->total_price;

            // Add date to account data if not existing yet.
            if (!isset($accounts[$accnr]->byDate[$sortDate])) {
                $accounts[$accnr]->byDate[$sortDate] = 0;
            }

            // Add orderline to account-on-date total.
            $accounts[$accnr]->byDate[$sortDate] += $orderline->total_price;
        }

        ksort($accounts);


        return view("omnomcom.accounts.orderlines-breakdown", [
            'accounts' => Account::generateAccountOverviewFromOrderlines($orderlines),
            'title' => "Accounts of withdrawal of " . date('d-m-Y', strtotime($withdrawal->date))
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->closed) {
            $request->session()->flash('flash_message', 'This withdrawal is already closed and cannot be edited.');
            return Redirect::back();
        }

        $date = strtotime($request->input('date'));
        if ($date === false) {
            $request->session()->flash('flash_message', 'Invalid date.');
            return Redirect::back();
        }

        $withdrawal->date = date('Y-m-d', $date);
        $withdrawal->save();

        $request->session()->flash('flash_message', 'Withdrawal updated.');
        return Redirect::route('omnomcom::withdrawal::show', ['id' => $withdrawal->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->closed) {
            $request->session()->flash('flash_message', 'This withdrawal is already closed and cannot be deleted.');
            return Redirect::back();
        }

        foreach ($withdrawal->orderlines as $orderline) {
            $orderline->withdrawal()->dissociate();
            $orderline->save();
        }

        $withdrawal->delete();

        $request->session()->flash('flash_message', 'Withdrawal deleted.');
        return Redirect::route('omnomcom::withdrawal::list');
    }

    /**
     * Delete a user from the specified withdrawal.
     *
     * @param $id Withdrawal id.
     * @param $user_id User id.
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function deleteFrom(Request $request, $id, $user_id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->closed) {
            $request->session()->flash('flash_message', 'This withdrawal is already closed and cannot be edited.');
            return Redirect::back();
        }

        $user = User::findOrFail($user_id);

        foreach ($withdrawal->orderlinesForUseR($user) as $orderline) {
            $orderline->withdrawal()->dissociate();
            $orderline->save();
        }

        $request->session()->flash('flash_message', 'Orderlines for ' . $user->name . ' removed from this withdrawal.');
        return Redirect::back();
    }

    /**
     * Generates a CSV file for the withdrawal and returns a download.
     *
     * @param $id Withdrawal id.
     * @return \Illuminate\Http\Response
     */
    public static function export(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        $seperator = ',';
        $response = implode($seperator, ['withdrawal_type', 'total', 'bank_machtigingid', 'signature_date', 'bank_bic', 'name', 'bank_iban', 'email']) . "\n";

        foreach ($withdrawal->users() as $user) {
            $response .= implode($seperator, [
                    ($user->bank ? 'FRST' : 'RCUR'),
                    number_format($withdrawal->totalForUser($user), 2, '.', ''),
                    $user->bank->machtigingid,
                    date('Y-m-d', strtotime($user->bank->created_at)),
                    $user->bank->bic,
                    $user->name,
                    $user->bank->iban,
                    $user->email
                ]) . "\n";
        }

        $headers = [
            'Content-Encoding' => 'UTF-8',
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="withdrawal-' . $withdrawal->id . '.csv"'
        ];

        return Response::make($response, 200, $headers);
    }

    /**
     * Close a withdrawal so no more changes can be made.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function close(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->closed) {
            $request->session()->flash('flash_message', 'This withdrawal is already closed and cannot be edited.');
            return Redirect::back();
        }

        foreach ($withdrawal->users() as $user) {
            $user->bank->is_first = false;
            $user->bank->save();
        }

        $withdrawal->closed = true;
        $withdrawal->save();

        $request->session()->flash('flash_message', 'The withdrawal is now closed. Changes cannot be made anymore.');
        return Redirect::back();
    }

    public function showForUser(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        return view('omnomcom.withdrawals.userhistory', ['withdrawal' => $withdrawal, 'orderlines' => $withdrawal->orderlinesForUser(Auth::user())]);
    }

    /**
     * Send an e-mail to all users in the withdrawal to notice them.
     *
     * @param $id Withdrawal id.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function email(Request $request, $id)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->closed) {
            $request->session()->flash('flash_message', 'This withdrawal is already closed so e-mails cannot be sent.');
            return Redirect::back();
        }

        foreach ($withdrawal->users() as $user) {
            Mail::to($user)->queue((new OmnomcomWithdrawalNotification($user, $withdrawal))->onQueue('medium'));
        }

        $request->session()->flash('flash_message', 'All e-mails have been queued.');
        return Redirect::back();
    }

    public function unwithdrawable()
    {
        $users = [];

        foreach (OrderLine::whereNull('payed_with_withdrawal')->get() as $orderline) {
            if ($orderline->isPayed()) continue;
            if ($orderline->user->bank) continue;
            if (!in_array($orderline->user->id, array_keys($users))) {
                $users[$orderline->user->id] = (object)[
                    'user' => $orderline->user,
                    'orderlines' => [],
                    'total' => 0
                ];
            }
            $users[$orderline->user->id]->orderlines[] = $orderline;
            $users[$orderline->user->id]->total += $orderline->total_price;
        }

        return view('omnomcom.unwithdrawable', ['users' => $users]);
    }

    /**
     * @return int The current sum of orderlines that are open.
     */
    public static function openOrderlinesSum()
    {
        $sum = 0;
        foreach (OrderLine::whereNull('payed_with_withdrawal')->get() as $orderline) {
            if ($orderline->isPayed()) continue;
            $sum += $orderline->total_price;
        }
        return $sum;
    }

    /**
     * @return int The total number of orderlines that are open.
     */
    public static function openOrderlinesTotal()
    {
        $total = 0;
        foreach (OrderLine::whereNull('payed_with_withdrawal')->get() as $orderline) {
            if ($orderline->isPayed()) continue;
            $total++;
        }
        return $total;
    }
}
