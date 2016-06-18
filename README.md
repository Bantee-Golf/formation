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
		'title'
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