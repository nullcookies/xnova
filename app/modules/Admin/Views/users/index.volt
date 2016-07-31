<div class="table-responsive">
	<table class="table table-striped table-hover table-advance">
		<thead>
			<tr>
				<th><a href="<?=$this->url->get('admin/users/?cmd=sort&type=id') ?>">ID</a></th>
				<th><a href="<?=$this->url->get('admin/users/?cmd=sort&type=username') ?>">Логин игрока</a></th>
				<th><a href="<?=$this->url->get('admin/users/?cmd=sort&type=email') ?>">E-Mail</a></th>
				<th><a href="<?=$this->url->get('admin/users/?cmd=sort&type=ip') ?>">IP</a></th>
				<th><a href="<?=$this->url->get('admin/users/?cmd=sort&type=create_time') ?>">Регистрация</a></th>
			</tr>
		</thead>
		<? foreach ($list AS $l): ?>
			<tr>
				<td><a href="/admin/users/edit/<?=$l['id'] ?>/"><?=$l['id'] ?></a></td>
				<td><a href="/admin/users/edit/<?=$l['id'] ?>/"><?=$l['username'] ?></a></td>
				<td><?=$l['email'] ?></td>
				<td><?=long2ip($l['ip']) ?></td>
				<td><?=date("d.m.Y H:i:s", $l['create_time']) ?><br><?=date("d.m.Y H:i:s", $l['onlinetime']) ?></td>
			</tr>
		<? endforeach; ?>
	</table>
</div>

<div class="row">
	<div class="col-md-12 col-sm-12">
		<div class="dataTables_paginate paging_bootstrap">
			<?=$pagination ?>
		</div>
	</div>
</div>