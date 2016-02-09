<?php

namespace Proto\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Proto\Member
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $name_first
 * @property string $name_last
 * @property string $name_initials
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $proto_mail
 * @property boolean $proto_mail_enabled
 * @property string $type
 * @property string $since
 * @property string $till
 * @property string $fee_cycle
 * @property string $birthdate
 * @property boolean $gender
 * @property string $nationality
 * @property string $phone
 * @property string $website
 * @property string $biography
 * @property string $association_primary
 * @property boolean $phone_visible
 * @property boolean $address_visible
 * @property boolean $receive_newsletter
 * @property boolean $receive_sms
 * @property-read \Proto\Models\User $user
 * @property boolean $primary_member
 */
class Member extends Model
{
    protected $table = 'members';

    protected $rules = array(
    );

    public function user()
    {
        return $this->belongsTo('Proto\Models\User');
    }
}