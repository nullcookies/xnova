{% if info is defined %}
	<div class="portlet box green">
		<div class="portlet-title">
			<div class="caption">Редактирование группы "{{ info['name'] }}"</div>
		</div>
		<div class="portlet-body form">
			<form action="{{ url('admin/groups/edit/'~info['id']~'/') }}" method="post" class="form-horizontal form-row-seperated">
				<div class="form-body">
					<div class="form-group">
						<label class="col-md-3 control-label">Имя</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="name" value="{{ info['name'] }}" title="">
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label">Права</label>
						<div class="col-md-9">
							<div class="row">
								<div class="col-xs-6"></div>
								<div class="col-xs-3 text-xs-center">Чтение</div>
								<div class="col-xs-3 text-xs-center">Изменение</div>
							</div>
							{% for module in modules %}
								<input type="hidden" name="module[{{ module['id'] }}]" value="0">
								<div class="row">
									<div class="col-xs-6">
										<label for="module_{{ module['id'] }}">{{ module['name'] }}</label>
									</div>
									<div class="col-xs-3 text-xs-center">
										<input id="module_{{ module['id'] }}" {{ rights[module['id']] is defined and rights[module['id']]['right_id'] == 1 ? 'checked' : '' }} type="checkbox" name="module[{{ module['id'] }}]" value="1">
									</div>
									<div class="col-xs-3 text-xs-center">
										<input {{ rights[module['id']] is defined and rights[module['id']]['right_id'] == 2 ? 'checked' : '' }} type="checkbox" name="module[{{ module['id'] }}]" value="2" title="">
									</div>
								</div>
							{% endfor %}
						</div>
					</div>
					<div class="form-actions">
						<button type="submit" name="save" class="btn green" value="Y">Сохранить</button>
					</div>
				</div>
			</form>
		</div>
	</div>
{% endif %}