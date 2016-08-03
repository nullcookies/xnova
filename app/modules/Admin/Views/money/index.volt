<div class="table-responsive">
	<table class="table table-striped table-hover table-advance">
		<thead>
			<tr>
				<th>transaction_id</th>
				<th>transaction_time</th>
				<th>method</th>
				<th>amount</th>
				<th>user_id</th>
			</tr>
		</thead>
		{% for m in parse['list'] %}
			<tr>
				<td>{{ m['transaction_id'] }}</td>
				<td>{{ m['transaction_time'] }}</td>
				<td>{{ m['method'] }}</td>
				<td>{{ m['amount'] }}</td>
				<td><a href="/admin/manager/data/username/{{ m['user'] }}/send/">{{ m['username'] ? m['username'] : '-' }}</a></td>
			</tr>
		{% endfor %}
	</table>
</div>
<div class="row">
	<div class="col-md-5 col-sm-12">
		<div class="dataTables_info">
			Совершенно <b>{{ parse['total'] }}</b> транзакций
		</div>
	</div>
	<div class="col-md-7 col-sm-12">
		<div class="dataTables_paginate paging_bootstrap">
			{{ parse['pagination'] }}
		</div>
	</div>
</div>