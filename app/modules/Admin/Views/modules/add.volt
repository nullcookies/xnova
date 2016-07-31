<div class="portlet box green">
	<div class="portlet-title">
		<div class="caption">Добавление нового модуля</div>
	</div>
	<div class="portlet-body form">
		<form action="<?=$this->url->get('admin/modules/add/') ?>" method="post" class="form-horizontal form-row-seperated">
			<div class="form-body">
				<div class="form-group">
					<label class="col-md-3 control-label"></label>
					<div class="col-md-9">
						<input id="active" type="checkbox" class="form-control" name="active" <?=($this->request->getPost('active', 'string', '') == 1 ? 'checked' : '') ?>>
						<label for="active">Активность</label>
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Алиас</label>
					<div class="col-md-9">
						<input type="text" class="form-control" name="alias" value="<?=$this->request->getPost('alias', 'string', '') ?>" title="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-md-3 control-label">Название</label>
					<div class="col-md-9">
						<input type="text" class="form-control" name="name" value="<?=$this->request->getPost('name', 'string', '') ?>" title="">
					</div>
				</div>
				<div class="form-actions">
					<button type="submit" name="save" class="btn green" value="Y">Добавить</button>
				</div>
			</div>
		</form>
	</div>
</div>