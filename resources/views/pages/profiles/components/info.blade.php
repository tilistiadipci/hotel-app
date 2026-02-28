<table style="width: 100%" class="table table-striped table-bordered" id="tableInfo">
    <tr>
        <td width="25%">{{ trans('common.name') }}</td>
        <td>{{ $user->profile->name ?? '' }}</td>
    </tr>
    <tr>
        <td>{{ trans('common.user.username') }}</td>
        <td>{{ $user->username ?? '' }}</td>
    </tr>
    <tr>
        <td>{{ trans('common.phone') }}</td>
        <td>{{ $user->profile->phone ?? '' }}</td>
    </tr>
    <tr>
        <td>{{ trans('common.address') }}</td>
        <td>{{ $user->profile->address ?? '' }}</td>
    </tr>
    <tr>
        <td>{{ trans('common.gender') }}</td>
        <td>{{ $user->profile->gender == 'male' ? trans('common.male') : trans('common.female') }}</td>
    </tr>
    <tr>
        <td>{{ trans('common.contact_name') }}</td>
        <td>{{ $user->profile->contact_name ?? '' }}</td>
    </tr>
    <tr>
        <td>{{ trans('common.email') }}</td>
        <td>{{ $user->email ?? '' }}</td>
    </tr>
    <tr>
        <td>{{ trans('common.last_login') }}</td>
        <td>{{ $user->last_login_at ?? '' }}</td>
    </tr>
</table>


@include('partials.buttons.btn-edit', [
    'url' => route('profile.edit'),
])
