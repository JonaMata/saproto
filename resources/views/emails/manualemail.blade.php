@extends('emails.template')

@section('body')

    {!! Markdown::convertToHtml($body) !!}

    <p>
        &nbsp;&nbsp;
    </p>

    <p>
        ---
    </p>

    <p>
        <sub>
            @if(is_array($destination))
                You receive this e-mail because you are subscribed to one or more of the following e-mail lists:
                @foreach($destination as $i => $list)
                    {{ $list['name'] }}
                    (
                    <a href="{{ route('unsubscribefromlist', ['hash' => EmailList::generateUnsubscribeHash($user_id, $list['id'])]) }}">unsubscribe</a>
                    ){{ ($i + 1 == count($destination) ? '' : ', ') }}
                @endforeach
            @elseif($destination == 'event')
                This is an e-mail directed to all participants of the {{ $event_name }} activity. You receive this e-mail because you signed up as a participant.
            @elseif($destination == 'users')
                This is an e-mail directed to all user accounts on the website of S.A. Proto. You receive this e-mail because you have an active user account.
            @elseif($destination == 'members')
                This is an e-mail directed to all members of S.A. Proto. You receive this e-mail because you have an active membership.
            @endif
        </sub>
    </p>

@endsection
