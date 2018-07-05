<?php namespace Xnitro\Mikrotik\Controllers;

use Lang;
use Flash;
use URL;
use Redirect;
use BackendMenu;
use Backend\Classes\Controller;
use System\Classes\ApplicationException;
use Xnitro\Mikrotik\Models\Settings as SettingsModel;

/**
 * Channels Back-end Controller
 */
class Settings extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
    ];

    public $formConfig = 'config_form.yaml';

    public $pageTitle = 'Server Mikrotik Settings';

	// public $requiredPermissions = ['kincir.quiz.access_quiz_settings'];

	public function __construct()
	{
		parent::__construct();
        BackendMenu::setContext('Xnitro.Mikrotik', 'subnetting', 'settings');
	}

	public function index()
	{
		$this->asExtension('FormController')->update();
	}

	/**
	 * Ajax handler for updating the form.
	 * @param int $recordId The model primary key to update.
	 * @return mixed
	 */
    public function index_onSave()
    {
        return $this->asExtension('FormController')->update_onSave();
    }

    public function formFindModelObject()
    {
        return SettingsModel::instance();
    }
}