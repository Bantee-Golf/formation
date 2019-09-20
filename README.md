# Auto-form Builder from Eloquent Models for Laravel 5 / 6

By default, it renders a Bootstrap 3, horizontal form layout.

## Installation

1. Add the repository to your `composer.json`

```
"repositories": [
        {
            "type":"vcs",
            "url":"git@bitbucket.org:elegantmedia/formation.git"
        }
    ],
```

2. Require the package through the command line

```
composer require emedia/formation
```

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

			// Method 1 - retrieve all items. eg. OfficeLocation::all();
			'options_entity' => 'App\Modules\HumanResources\Entities\OfficeLocation',

			// Method 2 - use `options_action` to call a method from repository
			// 'options_action' => 'App\Modules\HumanResources\Entities\ProjectsRepository@allAsList',
			// optional - you can pass an array of parameters to the 'options_action' method
			// 'options_action_params' => [$entity->id],

			// Method 3 - give the options directly
			// 'options' => [
            // 		'oneworld' => 'OneWorld',
            //		'skyteam' => 'SkyTeam',
            //	]

            // Method 4 - load entities from AJAX requests
            // [
	        //     'name' => 'external_inspector_id[]',
	        //    'display_name' => 'External Inspectors',
	        //    'type' => 'select',
	        //    'multiple' => true,   // optional
	        //    'options_ajax_data_route' => 'manage.properties.inspectors.json', // this is the route to return the data
	        // ],
		],
		[
			// multi-option selects
            'name' => 'destination_id[]',
            'display_name' => 'Destinations',
            'type' => 'select',
            'multiple' => true,
            'relationship' => 'destinations',       // optional. Use to resolve the relationship automatically.
            'options_entity' => Destination::class, // or use methods from single-selects (as listed above)
            'class' => 'select2',
            'value' => [1],     // default value
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
		[
            'name' => 'logo',
            'type' => 'file',
            'options' => [
                'disk' => 'public_content',		// required
                'disk_column' => 'disk',		// required
                'path_column' => 'logo_path',	// required - this must match with the 'name'. Otherwise you won't be able to edit the field
                'thumb_path_column' => 'logo_thumb_path',   // optional
            ],
        ],
        [
            'name' => 'Address',
            'type' => 'location',
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
{{ $form->renderSubmit() }}
```

### Configuration Options

To give more configuration options, instead of using the `$editable` property, override the `getEditableFields()` method. If you're using this method, the `$editable` property should be deleted from the model for clarity.

Following example shows how to customise the `location` field and add multiple Google Autocomplete Address fields.

```
public function getEditableFields()
{
	return [
		'name',
		[
			'name' => 'return_address',
			'display_name' => 'Return Address',
			'type' => 'location',
			'config' => lotus()->locationConfig()
							   ->setInputFieldPrefix('map3_')
							   ->setSearchBoxElementId('js-search-box3')
							   ->setMapElementId('map3')
							   ->setAutoCompleteOptions([
							        'types' => ['establishment'],
							        'componentRestrictions' => [
							            'country' => 'nz',
									]
							   ])
		],
		[
			'display_name' => 'Phone',
			'name' => '_map3_phone',        // let the auto-complete fill the phone number as well
			'class' => 'js-autocomplete',
		],
		'email',
	];
}
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
