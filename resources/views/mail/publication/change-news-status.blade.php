<p>Welcome to BeepnSay! </p>
<p>Dear Partner,</p>
@if(isset($new_status))
<p>Status is changed to {{$new_status}}</p>
@endif
@if(isset($reject_reason))
    <p>Why we reject it?</p>
    <p>{{$reject_reason}}</p>
@endif
@if(isset($publishing_date))
<p>Publishing date is {{$publishing_date}}</p>
@endif
<p>
Happy Publishing!<br>
Beepnsay Support Team
</p>