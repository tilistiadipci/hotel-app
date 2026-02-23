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
        <td>{{ trans('common.email') }}</td>
        <td>{{ $user->email ?? '' }}</td>
    </tr>
    <tr>
        <td>{{ trans('common.last_login') }}</td>
        <td>{{ $user->last_login_at ?? '' }}</td>
    </tr>
</table>
