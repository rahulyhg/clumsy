<?php namespace Clumsy\CMS\Controllers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Route;
use Illuminate\Http\Request;
use Cartalyst\Sentry\Facades\Laravel\Sentry;
use Clumsy\CMS\Controllers\AdminController;

class UsersController extends \BaseController {

	public static $validationRules = array(
		'first_name' => 'max:255',
		'last_name'  => 'max:255',
		'email'		 => 'required|email|max:255',
	);

	public function __construct()
	{
		$this->beforeFilter('@checkPermissions');

        $this->beforeFilter('csrf', array('only' => array('store', 'update', 'destroy')));

		with(new AdminController)->setupUser();

		View::share('resource', 'user');
	}

	public function checkPermissions(Route $route, Request $request)
	{
		$user = Sentry::getUser();
		$requested_user_id = $route->getParameter('user');

		if (!$user->hasAccess('users')) {

			if (!in_array($route->getName(), array('admin.user.edit', 'admin.user.update')) || $requested_user_id != $user->id) {

				return Redirect::route('clumsy/cms::admin.user.edit', $user->id)->with(array(
					'status'  => 'warning',
					'message' => trans('clumsy/cms::alerts.users.forbidden'),
				));
			}
		}
	}

	/**
	 * Display a listing of users
	 *
	 * @return Response
	 */
	public function index()
	{
		$data['items'] = Sentry::findAllUsers();

		$data['properties'] = array(
			'first_name' => trans('clumsy/cms::fields.first_name'),
			'last_name'  => trans('clumsy/cms::fields.last_name'),
			'email'		 => trans('clumsy/cms::fields.email'),
		);

        $data['title'] = trans('clumsy/cms::titles.users');

		return View::make('clumsy/cms::admin.users.index', $data);
	}

	/**
	 * Show the form for creating a new user
	 *
	 * @return Response
	 */
	public function create()
	{
		$data['title'] = trans('clumsy/cms::titles.new_user');

        $data['form_fields'] = 'clumsy/cms::admin.users.fields';

		$data['edited_user_id'] = 'new';
		$data['edited_user_group'] = '';

        return View::make('clumsy/cms::admin.users.edit', $data);
	}

	/**
	 * Store a newly created user in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$rules = array_merge(
			self::$validationRules,
			array(
				'password' => 'required|min:6|max:255',
				'confirm_password' => 'required|same:password',
			)
		);

		$rules['email'] .= '|unique:users';

		$validator = Validator::make($data = Input::all(), $rules);

		if ($validator->fails())
		{
			return Redirect::back()
				->withErrors($validator)
				->withInput()
                ->with(array(
                    'status'   => 'warning',
                    'message'  => trans('clumsy/cms::alerts.invalid'),
                ));
		}

        $new_user = Sentry::register(array(
            'first_name' => Input::get('first_name'),
            'last_name'  => Input::get('last_name'),
            'email'      => Input::get('email'),
            'password'   => Input::get('password'),
        ));

        // Auto-activate
		$new_user->attemptActivation($new_user->getActivationCode());

		$group = Sentry::findGroupByName(Input::get('group'));
		$new_user->addGroup($group);

		return Redirect::route('admin.user.index')->with(array(
           'status'   => 'success',
           'message'  => trans('clumsy/cms::alerts.user.added'),
        ));
	}

	/**
	 * Display the specified user.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
        return Redirect::route('admin.user.edit', $id);
	}

	/**
	 * Show the form for editing the specified user.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$data['item'] = Sentry::findUserById($id);

		if ($self = (Sentry::getUser()->id == $id)) {
			
			$data['supress_delete'] = true;
		}

        $data['title'] = $self ? trans('clumsy/cms::titles.profile') : trans('clumsy/cms::titles.edit_user');

        $data['edited_user_id'] = $id;
        $data['edited_user_group'] = $data['item']->getGroups()->first()->name;

        $data['form_fields'] = 'clumsy/cms::admin.users.fields';

		return View::make('clumsy/cms::admin.users.edit', $data);
	}

	/**
	 * Update the specified user in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$user = Sentry::findUserById($id);

		$rules = self::$validationRules;

		if ($new_password = (Input::has('new_password') && Input::get('new_password') != '')) {

			$rules['new_password'] = 'required|min:6|max:255';
			$rules['confirm_new_password'] = 'required|same:new_password';
		}

		$validator = Validator::make($data = Input::all(), $rules);

		if ($validator->fails())
		{
			return Redirect::back()
				->withErrors($validator)
				->withInput()
                ->with(array(
                    'status'   => 'warning',
                    'message'  => trans('clumsy/cms::alerts.invalid'),
                ));
		}

		if ($new_password) {

			$data['password'] = $data['new_password'];
		}
		unset($data['new_password']);
		unset($data['confirm_new_password']);

		if (Input::has('group')) {

			$groups = Sentry::findAllGroups();

			foreach ($groups as $group) {

				$user->removeGroup($group);
			}

			$group = Sentry::findGroupByName(Input::get('group'));

			$user->addGroup($group);

			unset($data['group']);
		}

		$user->update($data);

        $url = URL::route('admin.user.index');

		if (!$user->hasAccess('users')) {

			$url = URL::route('admin.user.edit', $user->id);
		}

		return Redirect::to($url)->with(array(
           'status'   => 'success',
           'message'  => trans('clumsy/cms::alerts.user.updated'),
        ));
	}

	/**
	 * Remove the specified user from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		if (Sentry::getUser()->id == $id) {

			$status = 'warning';
			$message = trans('clumsy/cms::alerts.user.suicide');

		} else {
			
			$user = Sentry::findUserById($id);

		    $user->delete();

			$status = 'success';
			$message = trans('clumsy/cms::alerts.user.deleted');
		}

		return Redirect::route('admin.user.index')->with(array(
           'status'   => $status,
           'message'  => $message,
        ));
	}

}
