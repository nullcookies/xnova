{% if msg %}{{ msg }}{% endif %}
<form action="{{ url('messages/write/'~id~'/') }}" method="post" {% if isPopup %}class="popup"{% endif %}>
	<table class="table form-group">
		{% if isPopup is false %}
		<tr>
			<td class="c" colspan="2">Отправка сообщения</td>
		</tr>
		{% endif %}
		<tr>
			<th>Получатель: <input type="text" name="to" id="to" style="width: 100%" value="{{ to }}"  title=""></th>
		</tr>
		<tr>
			<th class="p-a-0">
				<div id="editor"></div>
				<script type="text/javascript">edToolbar('text');</script>
				<textarea name="text" id="text" rows="15" onkeypress="if((event.ctrlKey) && ((event.keyCode==10)||(event.keyCode==13))) submit()" title="">{{ text }}</textarea></th>
		</tr>
		<tr>
			<th colspan="2"><input type="submit" value="Отправить"></th>
		</tr>
	</table>
	<div id="showpanel" style="display:none">
		<table align="center" class="table">
			<tr>
				<td class="c"><b>Предварительный просмотр</b></td>
			</tr>
			<tr>
				<td class="b"><span id="showbox"></span></td>
			</tr>
		</table>
	</div>
</form>