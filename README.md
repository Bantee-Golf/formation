# Auto-form Builder from Eloquent Models for Laravel 5

By default, it renders a Bootstrap 3, horizontal form layout.

## How to use

**Prepare the model**
```
	use GeneratesFields;

	protected $editable = [
		[
			'name' => 'first_name',
			'display_name' => 'Your first name',    // (optional label)
			'value' => '1234',  // (optional value)
		],
		[
			'name' => 'last_name'
		],
		'title',
		[
			'name' => 'office_location_id',
			'display_name' => 'Office Location',
			'type' => 'select',
			// retrieve all items. eg. OfficeLocation::all();
			'options_entity' => 'App\Modules\HumanResources\Entities\OfficeLocation',
			// alternatively use `options_action` to call a method from repository
			// 'options_action' => 'App\Modules\HumanResources\Entities\ProjectsRepository@allAsList',
			// optional - you can pass an array of parameters to the 'options_action' method
			// 'options_action_params' => [$entity->id],
		],
        [
			'name' => 'joined_at',
			'display_name' => 'Joined Date',
			'type' => 'date',
			'data' => [
				// TODO: subtract-x-days, add-x-days
				'min_date' => '01/May/2010',
				'max_date' => null,
			]
		],
        [
			'name' => 'currency',
			'type' => 'select',
			'options' => [
				'LKR' => 'LKR',
				'AUD' => 'AUD'
			]
		],
	];
	
```

In the controller
```
	$entity = new User();
	$entity->first_name = 'Kim';
	$entity->last_name = 'Kardashian';

	$form = new Formation($entity);
	
	return view('user.profile', compact('form'));
```

Then in the view
```
{{ $form->render() }}
```

### API
```
	$form = new Formation($entity);
	
	// optional
	 
	// set fields manually
	// $form->setFields($entity->getEditableFields());
	
	// set the values from model
	// $form->setFieldValuesFromModel($entity);
	
	// set individual field values
	// $form->setFieldValue('first_name', 'Khloe');
```