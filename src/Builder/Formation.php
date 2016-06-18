<?php
namespace EMedia\Formation\Builder;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;
use Illuminate\View\Factory;

class Formation
{

	protected $fields;

	use BuildsFormElements;
	use BuildsHtml;

	public function __construct(Model $entity = null)
	{
		$this->fields = new Collection();
		if ($entity) {
			$this->setModel($entity);
		}
	}

	public function addField($name, $type, $order = null)
	{
		
	}

	/*
	 * This generates the following horizontal layout
	 *
	 * <div class="form-group">
            <label for="" class="col-sm-2 control-label">First Name</label>
            <div class="col-sm-10">
                <input type="" class="form-control" name="name" value="{{ $entity->first_name }}">
            </div>
        </div>
	 *
	 */
	public function render()
	{
		$labelLayoutClass = 'col-sm-2';
		$fieldLayoutClass = 'col-sm-10';

		$renderedContent = '';

		$user = auth()->user();

		foreach($this->fields as $field) {

			// check user permissions
			// if the user doesn't have the given role, don't show the field
			if (isset($field['roles'])) {
				$allowedRoles = $field['roles'];
				if (!$user->is($allowedRoles)) {
					continue;
				}
			}

			$label = $this->label($field['name'], $field['display_name'], [
				'class' => $labelLayoutClass . ' control-label',
			]);

			// TODO: add vertical layouts

			// add the field
			// TODO: add other field types
			$options = [];
			$options['class'] = 'form-control';
			if (!empty($field['placeholder'])) $options['placeholder'] = $field['placeholder'];

			if ($field['type'] === 'text') {

				$field = $this->input('text', $field['name'], $field['value'], $options);

			} else if ($field['type'] === 'date') {

				$options['class'] .= ' ' . 'js-datepicker';
				$options['data-date-format'] = 'DD/MMM/YYYY';
				$inputDate = '';
				if ($field['value'] instanceof Carbon) {
					$options['data-default-date'] = $field['value']->format('d/M/Y');
				}
				$field = $this->input('text', $field['name'], $inputDate, $options);

			} else if ($field['type'] === 'select') {
				// build the options
				$optionsList = [];
				if (!empty($field['options'])) {
					$optionsList = $field['options'];
				} else if (isset($field['options_action'])) {
					// split the action string
					// eg 'ProjectsRepository@allAsList'
					preg_match_all('/^(.*)@(.*)$/i', $field['options_action'], $matches);
					if (!count($matches) === 3)
						throw new Exception("Invalid action {$field['options_action']}.");

					// build the class and fetch the options
					$actionsClass = app($matches[1][0]);
					$optionsList = $actionsClass->$matches[2][0]();
				} else if (isset($field['options_entity'])) {
					$actionsClass = app($field['options_entity']);
					$optionsList = $actionsClass->all()->pluck('name', 'id');
				}
				$field = $this->select($field['name'], $optionsList, $field['value'], $options);
			}

			$fieldWrapper = $this->tag('div', $field->toHtml(), [
				'class' => $fieldLayoutClass
			]);

			// TODO: add vertical layouts
			$formGroupWrapper = $this->tag('div',
				$label->toHtml() . $fieldWrapper->toHtml(), [
					'class' => 'form-group'
				]);

			$renderedContent .= $formGroupWrapper->toHtml();
		}

		return $this->toHtmlString($renderedContent);
	}

	public function renderSubmit()
	{
		$htmlContent = '<div class="form-group">
            <div class="col-sm-10 col-sm-offset-2">
                <a href="' . url()->previous() . '" class="btn btn-default pull-right">Cancel</a>
                <button type="submit" class="btn btn-success text-right">Save</button>
            </div>
        </div>';

        return $this->toHtmlString($htmlContent);
	}

	/**
	 *
	 * Set the fields from the model's config
	 * eg. Use the following in the model
	 *
	 * 	protected $editable = [
				[
					'name' => 'first_name',
					'display_name' => 'Your first name',
					'value' => '1234',
				],
				[
					'name' => 'last_name'
				],
				[
					'name' => 'title'
				],
	 			[
					'name' => 'project_status_id',
					'display_name' => 'Project Status',
					'type' => 'select',
					// 'options' => [
					//		1 => 'Upcoming',
					// 		2 => 'Wireframing'
					// ],
					'options_action' => 'App\Modules\Projects\Entities\ProjectStatusesRepository@allAsList'
				]
			];
	 *
	 * @param array $editableFields
	 */
	public function setFields(array $editableFields)
	{

		$this->fields = new Collection();
		foreach ($editableFields as $editableField) {

			if (is_string($editableField)) {

				$editableField = ['name' => $editableField];
				$editableField['type'] = 'text';
				$editableField['display_name'] = $this->getLabelFromFieldName($editableField['name']);
				$editableField['value'] = '';
				$this->fields->push($editableField);

			} else if (!empty($editableField['name'])) {

				// TODO: get the type of the field. Only text type is supported for now
				// $fieldType = $this->getFieldType($editableField);
				if (empty($editableField['type'])) {
					$editableField['type'] = 'text';
				}
				
				if ($editableField['type'] === 'select' && (
					empty($editableField['options']) && empty($editableField['options_action']) && empty($editableField['options_entity']))) {
					throw new Exception("A select field must an `options` specifier.");
				}

				// name of the field (i.e. label)
				if (empty($editableField['display_name'])) {
					$editableField['display_name'] = $this->getLabelFromFieldName($editableField['name']);
				}

				// default value of the field
				if (empty($editableField['value'])) {
					$editableField['value'] = '';
				}

				// placeholder
				if (empty($editableField['placeholder'])) {
					$editableField['placeholder'] = '';
				}

				$this->fields->push($editableField);
			}

		}
	}

	private function getLabelFromFieldName($fieldName)
	{
		return title_case(reverse_snake_case($fieldName));
	}

	public function setModel(Model $entity)
	{
		// set the fields
		if (!method_exists($entity, 'getEditableFields')) return false;

		// set the field parameters
		$this->setFields($entity->getEditableFields());

		// set default values
		$this->setFieldValuesFromModel($entity);
	}

	/**
	 *
	 * Set the default values of the fields
	 *
	 * @param $entity
	 */
	public function setFieldValuesFromModel(Model $entity)
	{
		// copy the field values, because we'll modify this later
		$fields = $this->fields;

		// look if the model has a the values set, and if so, add them to the fields
		foreach($fields as $fieldData) {
			 // get the field name
			$value = $entity->getAttributeValue($fieldData['name']);

			if (!empty($value)) {
				$this->setFieldValue($fieldData['name'], $value);
			}
		}
	}

	/**
	 *
	 * Go set the value of field(s) to the given value
	 *
	 * @param $fieldName
	 * @param $value
	 *
	 * @return bool
	 */
	private function setFieldValue($fieldName, $value)
	{
		if (empty($value)) return false;

		// go through all fields, and update the given value
		$this->fields->transform(function ($fieldData) use ($fieldName, $value) {
			if ($fieldData['name'] === $fieldName) {
				$fieldData['value'] = $value;
			}
			return $fieldData;
		});

		return true;
	}

//	private function getFieldType($editableField)
//	{
//		if (empty($editableField['type'])) return 'text';
//
//		switch ($editableField['type']) {
//			case 'text':
//				return 'text';
//				break;
//			default:
//				return 'text';
//		}
//	}



}