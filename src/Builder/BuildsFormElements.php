<?php
namespace EMedia\Formation\Builder;

use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait BuildsFormElements
{

	/**
	 * The CSRF token used by the form builder.
	 *
	 * @var string
	 */
	protected $csrfToken;

	/**
	 * The session store implementation.
	 *
	 * @var \Illuminate\Session\SessionInterface
	 */
	protected $session;

	/**
	 * The current model instance for the form.
	 *
	 * @var mixed
	 */
	protected $model;

	/**
	 * An array of label names we've created.
	 *
	 * @var array
	 */
	protected $labels = [];

	/**
	 * The reserved form open attributes.
	 *
	 * @var array
	 */
	protected $reserved = ['method', 'url', 'route', 'action', 'files'];

	/**
	 * The form methods that should be spoofed, in uppercase.
	 *
	 * @var array
	 */
	protected $spoofedMethods = ['DELETE', 'PATCH', 'PUT'];

	/**
	 * The types of inputs to not fill values on by default.
	 *
	 * @var array
	 */
	protected $skipValueTypes = ['file', 'password', 'checkbox', 'radio'];

	/**
	 * Generate a hidden field with the current CSRF token.
	 *
	 * @return string
	 */
	public function token()
	{
		$token = ! empty($this->csrfToken) ? $this->csrfToken : $this->session->getToken();

		return $this->hidden('_token', $token);
	}

	/**
	 * Create a form label element.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function label($name, $value = null, $options = [])
	{
		$this->labels[] = $name;

		$options = $this->attributes($options);

		$value = e($this->formatLabel($name, $value));

		return $this->toHtmlString('<label for="' . $name . '"' . $options . '>' . $value . '</label>');
	}

	/**
	 * Format the label value.
	 *
	 * @param  string      $name
	 * @param  string|null $value
	 *
	 * @return string
	 */
	protected function formatLabel($name, $value)
	{
		return $value ?: ucwords(str_replace('_', ' ', $name));
	}

	/**
	 * Create a form input field.
	 *
	 * @param  string $type
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function input($type, $name, $value = null, $options = [])
	{
		if (! isset($options['name'])) {
			$options['name'] = $name;
		}

		// We will get the appropriate value for the given field. We will look for the
		// value in the session for the value in the old input data then we'll look
		// in the model instance if one is set. Otherwise we will just use empty.
		$id = $this->getIdAttribute($name, $options);

		if (! in_array($type, $this->skipValueTypes)) {
			$value = $this->getValueAttribute($name, $value);
		}

		// Once we have the type, value, and ID we can merge them into the rest of the
		// attributes array so we can convert them into their HTML attribute format
		// when creating the HTML element. Then, we will return the entire input.
		$merge = compact('type', 'value', 'id');

		$options = array_merge($options, $merge);

		return $this->toHtmlString('<input' . $this->attributes($options) . '>');
	}

	/**
	 * Create a text input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function text($name, $value = null, $options = [])
	{
		return $this->input('text', $name, $value, $options);
	}

	/**
	 * Create a password input field.
	 *
	 * @param  string $name
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function password($name, $options = [])
	{
		return $this->input('password', $name, '', $options);
	}

	/**
	 * Create a hidden input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function hidden($name, $value = null, $options = [])
	{
		return $this->input('hidden', $name, $value, $options);
	}

	/**
	 * Create an e-mail input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function email($name, $value = null, $options = [])
	{
		return $this->input('email', $name, $value, $options);
	}

	/**
	 * Create a tel input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function tel($name, $value = null, $options = [])
	{
		return $this->input('tel', $name, $value, $options);
	}

	/**
	 * Create a number input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function number($name, $value = null, $options = [])
	{
		return $this->input('number', $name, $value, $options);
	}

	/**
	 * Create a date input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function date($name, $value = null, $options = [])
	{
		if ($value instanceof DateTime) {
			$value = $value->format('Y-m-d');
		}

		return $this->input('date', $name, $value, $options);
	}

	/**
	 * Create a datetime input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function datetime($name, $value = null, $options = [])
	{
		if ($value instanceof DateTime) {
			$value = $value->format(DateTime::RFC3339);
		}

		return $this->input('datetime', $name, $value, $options);
	}

	/**
	 * Create a datetime-local input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function datetimeLocal($name, $value = null, $options = [])
	{
		if ($value instanceof DateTime) {
			$value = $value->format('Y-m-d\TH:i');
		}

		return $this->input('datetime-local', $name, $value, $options);
	}

	/**
	 * Create a time input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function time($name, $value = null, $options = [])
	{
		return $this->input('time', $name, $value, $options);
	}

	/**
	 * Create a url input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function url($name, $value = null, $options = [])
	{
		return $this->input('url', $name, $value, $options);
	}

	/**
	 * Create a file input field.
	 *
	 * @param  string $name
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function file($name, $options = [])
	{
		return $this->input('file', $name, null, $options);
	}

	/**
	 * Create a textarea input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function textarea($name, $value = null, $options = [])
	{
		if (! isset($options['name'])) {
			$options['name'] = $name;
		}

		// Next we will look for the rows and cols attributes, as each of these are put
		// on the textarea element definition. If they are not present, we will just
		// assume some sane default values for these attributes for the developer.
		$options = $this->setTextAreaSize($options);

		$options['id'] = $this->getIdAttribute($name, $options);

		$value = (string) $this->getValueAttribute($name, $value);

		unset($options['size']);

		// Next we will convert the attributes into a string form. Also we have removed
		// the size attribute, as it was merely a short-cut for the rows and cols on
		// the element. Then we'll create the final textarea elements HTML for us.
		$options = $this->attributes($options);

		return $this->toHtmlString('<textarea' . $options . '>' . e($value) . '</textarea>');
	}

	/**
	 * Set the text area size on the attributes.
	 *
	 * @param  array $options
	 *
	 * @return array
	 */
	protected function setTextAreaSize($options)
	{
		if (isset($options['size'])) {
			return $this->setQuickTextAreaSize($options);
		}

		// If the "size" attribute was not specified, we will just look for the regular
		// columns and rows attributes, using sane defaults if these do not exist on
		// the attributes array. We'll then return this entire options array back.
		$cols = Arr::get($options, 'cols', 50);

		$rows = Arr::get($options, 'rows', 10);

		return array_merge($options, compact('cols', 'rows'));
	}

	/**
	 * Set the text area size using the quick "size" attribute.
	 *
	 * @param  array $options
	 *
	 * @return array
	 */
	protected function setQuickTextAreaSize($options)
	{
		$segments = explode('x', $options['size']);

		return array_merge($options, ['cols' => $segments[0], 'rows' => $segments[1]]);
	}

	/**
	 * Create a select box field.
	 *
	 * @param  string $name
	 * @param  array  $list
	 * @param  string $selected
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function select($name, $list = [], $selected = null, $options = [])
	{
		// When building a select box the "value" attribute is really the selected one
		// so we will use that when checking the model or session for a value which
		// should provide a convenient method of re-populating the forms on post.
		$selected = $this->getValueAttribute($name, $selected);

		$options['id'] = $this->getIdAttribute($name, $options);

		if (! isset($options['name'])) {
			$options['name'] = $name;
		}

		// We will simply loop through the options and build an HTML value for each of
		// them until we have an array of HTML declarations. Then we will join them
		// all together into one single HTML element that can be put on the form.
		$html = [];

		if (isset($options['placeholder'])) {
			$html[] = $this->placeholderOption($options['placeholder'], $selected);
			unset($options['placeholder']);
		}

		foreach ($list as $value => $display) {
			$html[] = $this->getSelectOption($display, $value, $selected);
		}

		// Once we have all of this HTML, we can join this into a single element after
		// formatting the attributes into an HTML "attributes" string, then we will
		// build out a final select statement, which will contain all the values.
		$options = $this->attributes($options);

		$list = implode('', $html);

		return $this->toHtmlString("<select{$options}>{$list}</select>");
	}

	/**
	 * Create a select range field.
	 *
	 * @param  string $name
	 * @param  string $begin
	 * @param  string $end
	 * @param  string $selected
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function selectRange($name, $begin, $end, $selected = null, $options = [])
	{
		$range = array_combine($range = range($begin, $end), $range);

		return $this->select($name, $range, $selected, $options);
	}

	/**
	 * Create a select year field.
	 *
	 * @param  string $name
	 * @param  string $begin
	 * @param  string $end
	 * @param  string $selected
	 * @param  array  $options
	 *
	 * @return mixed
	 */
	public function selectYear()
	{
		return call_user_func_array([$this, 'selectRange'], func_get_args());
	}

	/**
	 * Create a select month field.
	 *
	 * @param  string $name
	 * @param  string $selected
	 * @param  array  $options
	 * @param  string $format
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function selectMonth($name, $selected = null, $options = [], $format = '%B')
	{
		$months = [];

		foreach (range(1, 12) as $month) {
			$months[$month] = strftime($format, mktime(0, 0, 0, $month, 1));
		}

		return $this->select($name, $months, $selected, $options);
	}

	/**
	 * Get the select option for the given value.
	 *
	 * @param  string $display
	 * @param  string $value
	 * @param  string $selected
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function getSelectOption($display, $value, $selected)
	{
		if (is_array($display)) {
			return $this->optionGroup($display, $value, $selected);
		}

		return $this->option($display, $value, $selected);
	}

	/**
	 * Create an option group form element.
	 *
	 * @param  array  $list
	 * @param  string $label
	 * @param  string $selected
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	protected function optionGroup($list, $label, $selected)
	{
		$html = [];

		foreach ($list as $value => $display) {
			$html[] = $this->option($display, $value, $selected);
		}

		return $this->toHtmlString('<optgroup label="' . e($label) . '">' . implode('', $html) . '</optgroup>');
	}

	/**
	 * Create a select element option.
	 *
	 * @param  string $display
	 * @param  string $value
	 * @param  string $selected
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	protected function option($display, $value, $selected)
	{
		$selected = $this->getSelectedValue($value, $selected);

		$options = ['value' => $value, 'selected' => $selected];

		return $this->toHtmlString('<option' . $this->attributes($options) . '>' . e($display) . '</option>');
	}

	/**
	 * Create a placeholder select element option.
	 *
	 * @param $display
	 * @param $selected
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	protected function placeholderOption($display, $selected)
	{
		$selected = $this->getSelectedValue(null, $selected);

		$options = compact('selected');
		$options['value'] = '';

		return $this->toHtmlString('<option' . $this->attributes($options) . '>' . e($display) . '</option>');
	}

	/**
	 * Determine if the value is selected.
	 *
	 * @param  string $value
	 * @param  string $selected
	 *
	 * @return null|string
	 */
	protected function getSelectedValue($value, $selected)
	{
		if (is_array($selected)) {
			return in_array($value, $selected) ? 'selected' : null;
		}

		return ((string) $value == (string) $selected) ? 'selected' : null;
	}

	/**
	 * Create a checkbox input field.
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 * @param  bool   $checked
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function checkbox($name, $value = 1, $checked = null, $options = [])
	{
		return $this->checkable('checkbox', $name, $value, $checked, $options);
	}

	/**
	 * Create a radio button input field.
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 * @param  bool   $checked
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function radio($name, $value = null, $checked = null, $options = [])
	{
		if (is_null($value)) {
			$value = $name;
		}

		return $this->checkable('radio', $name, $value, $checked, $options);
	}

	/**
	 * Create a checkable input field.
	 *
	 * @param  string $type
	 * @param  string $name
	 * @param  mixed  $value
	 * @param  bool   $checked
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	protected function checkable($type, $name, $value, $checked, $options)
	{
		$checked = $this->getCheckedState($type, $name, $value, $checked);

		if ($checked) {
			$options['checked'] = 'checked';
		}

		return $this->input($type, $name, $value, $options);
	}

	/**
	 * Get the check state for a checkable input.
	 *
	 * @param  string $type
	 * @param  string $name
	 * @param  mixed  $value
	 * @param  bool   $checked
	 *
	 * @return bool
	 */
	protected function getCheckedState($type, $name, $value, $checked)
	{
		switch ($type) {
			case 'checkbox':
				return $this->getCheckboxCheckedState($name, $value, $checked);

			case 'radio':
				return $this->getRadioCheckedState($name, $value, $checked);

			default:
				return $this->getValueAttribute($name) == $value;
		}
	}

	/**
	 * Get the check state for a checkbox input.
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 * @param  bool   $checked
	 *
	 * @return bool
	 */
	protected function getCheckboxCheckedState($name, $value, $checked)
	{
		if (isset($this->session) && ! $this->oldInputIsEmpty() && is_null($this->old($name))) {
			return false;
		}

		if ($this->missingOldAndModel($name)) {
			return $checked;
		}

		$posted = $this->getValueAttribute($name, $checked);

		if (is_array($posted)) {
			return in_array($value, $posted);
		} elseif ($posted instanceof Collection) {
			return $posted->contains('id', $value);
		} else {
			return (bool) $posted;
		}
	}

	/**
	 * Get the check state for a radio input.
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 * @param  bool   $checked
	 *
	 * @return bool
	 */
	protected function getRadioCheckedState($name, $value, $checked)
	{
		if ($this->missingOldAndModel($name)) {
			return $checked;
		}

		return $this->getValueAttribute($name) == $value;
	}

	/**
	 * Determine if old input or model input exists for a key.
	 *
	 * @param  string $name
	 *
	 * @return bool
	 */
	protected function missingOldAndModel($name)
	{
		return (is_null($this->old($name)) && is_null($this->getModelValueAttribute($name)));
	}

	/**
	 * Create a HTML reset input element.
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function reset($value, $attributes = [])
	{
		return $this->input('reset', null, $value, $attributes);
	}

	/**
	 * Create a HTML image input element.
	 *
	 * @param  string $url
	 * @param  string $name
	 * @param  array  $attributes
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function image_element($url, $name = null, $attributes = [])
	{
		$attributes['src'] = $this->url->asset($url);

		return $this->input('image', $name, null, $attributes);
	}

	/**
	 * Create a color input field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function color($name, $value = null, $options = [])
	{
		return $this->input('color', $name, $value, $options);
	}

	/**
	 * Create a submit button element.
	 *
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function submit($value = null, $options = [])
	{
		return $this->input('submit', null, $value, $options);
	}

	/**
	 * Create a button element.
	 *
	 * @param  string $value
	 * @param  array  $options
	 *
	 * @return \Illuminate\Support\HtmlString
	 */
	public function button($value = null, $options = [])
	{
		if (! array_key_exists('type', $options)) {
			$options['type'] = 'button';
		}

		return $this->toHtmlString('<button' . $this->attributes($options) . '>' . $value . '</button>');
	}

	/**
	 * Parse the form action method.
	 *
	 * @param  string $method
	 *
	 * @return string
	 */
	protected function getMethod($method)
	{
		$method = strtoupper($method);

		return $method != 'GET' ? 'POST' : $method;
	}

	/**
	 * Get the form action from the options.
	 *
	 * @param  array $options
	 *
	 * @return string
	 */
	protected function getAction(array $options)
	{
		// We will also check for a "route" or "action" parameter on the array so that
		// developers can easily specify a route or controller action when creating
		// a form providing a convenient interface for creating the form actions.
		if (isset($options['url'])) {
			return $this->getUrlAction($options['url']);
		}

		if (isset($options['route'])) {
			return $this->getRouteAction($options['route']);
		}

		// If an action is available, we are attempting to open a form to a controller
		// action route. So, we will use the URL generator to get the path to these
		// actions and return them from the method. Otherwise, we'll use current.
		elseif (isset($options['action'])) {
			return $this->getControllerAction($options['action']);
		}

		return $this->url->current();
	}

	/**
	 * Get the action for a "url" option.
	 *
	 * @param  array|string $options
	 *
	 * @return string
	 */
	protected function getUrlAction($options)
	{
		if (is_array($options)) {
			return $this->url->to($options[0], array_slice($options, 1));
		}

		return $this->url->to($options);
	}

	/**
	 * Get the action for a "route" option.
	 *
	 * @param  array|string $options
	 *
	 * @return string
	 */
	protected function getRouteAction($options)
	{
		if (is_array($options)) {
			return $this->url->route($options[0], array_slice($options, 1));
		}

		return $this->url->route($options);
	}

	/**
	 * Get the action for an "action" option.
	 *
	 * @param  array|string $options
	 *
	 * @return string
	 */
	protected function getControllerAction($options)
	{
		if (is_array($options)) {
			return $this->url->action($options[0], array_slice($options, 1));
		}

		return $this->url->action($options);
	}

	/**
	 * Get the form appendage for the given method.
	 *
	 * @param  string $method
	 *
	 * @return string
	 */
	protected function getAppendage($method)
	{
		list($method, $appendage) = [strtoupper($method), ''];

		// If the HTTP method is in this list of spoofed methods, we will attach the
		// method spoofer hidden input to the form. This allows us to use regular
		// form to initiate PUT and DELETE requests in addition to the typical.
		if (in_array($method, $this->spoofedMethods)) {
			$appendage .= $this->hidden('_method', $method);
		}

		// If the method is something other than GET we will go ahead and attach the
		// CSRF token to the form, as this can't hurt and is convenient to simply
		// always have available on every form the developers creates for them.
		if ($method != 'GET') {
			$appendage .= $this->token();
		}

		return $appendage;
	}

	/**
	 * Get the ID attribute for a field name.
	 *
	 * @param  string $name
	 * @param  array  $attributes
	 *
	 * @return string
	 */
	public function getIdAttribute($name, $attributes)
	{
		if (array_key_exists('id', $attributes)) {
			return $attributes['id'];
		}

		if (in_array($name, $this->labels)) {
			return $name;
		}
	}

	/**
	 * Get the value that should be assigned to the field.
	 *
	 * @param  string $name
	 * @param  string $value
	 *
	 * @return mixed
	 */
	public function getValueAttribute($name, $value = null)
	{
		if (is_null($name)) {
			return $value;
		}

		if (! is_null($this->old($name))) {
			return $this->old($name);
		}

		if (! is_null($value)) {
			return $value;
		}

		if (isset($this->model)) {
			return $this->getModelValueAttribute($name);
		}
	}

	/**
	 * Get the model value that should be assigned to the field.
	 *
	 * @param  string $name
	 *
	 * @return mixed
	 */
	protected function getModelValueAttribute($name)
	{
		if (method_exists($this->model, 'getFormValue')) {
			return $this->model->getFormValue($name);
		}

		return data_get($this->model, $this->transformKey($name));
	}

	/**
	 * Get a value from the session's old input.
	 *
	 * @param  string $name
	 *
	 * @return mixed
	 */
	public function old($name)
	{
		if (isset($this->session)) {
			return $this->session->getOldInput($this->transformKey($name));
		}
	}

	/**
	 * Determine if the old input is empty.
	 *
	 * @return bool
	 */
	public function oldInputIsEmpty()
	{
		return (isset($this->session) && count($this->session->getOldInput()) == 0);
	}

	/**
	 * Transform key from array to dot syntax.
	 *
	 * @param  string $key
	 *
	 * @return mixed
	 */
	protected function transformKey($key)
	{
		return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
	}

	/**
	 * Get the session store implementation.
	 *
	 * @return  \Illuminate\Session\SessionInterface  $session
	 */
	public function getSessionStore()
	{
		return $this->session;
	}

	/**
	 * Set the session store implementation.
	 *
	 * @param  \Illuminate\Session\SessionInterface $session
	 *
	 * @return $this
	 */
	public function setSessionStore(SessionInterface $session)
	{
		$this->session = $session;

		return $this;
	}

}
