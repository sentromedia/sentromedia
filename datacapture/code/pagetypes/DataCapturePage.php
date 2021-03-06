<?php
/**
 * Represents the data capture page
 * 
 * @author Julius <julius@greenbrainer.com>
 * @copyright Copyright (c) 2015, Julius
 */
class DataCapturePage extends Page {

	/**
	 * Set properties
	 * 
	 * @var array
	 */
	private static $db = array(
		'FormTitle' => 'Text', 
		'FormDescription' => 'Text', 
		'ListID' => 'Text', 
		'MergeTag' => 'Varchar',
		'FormButtonTitle' => 'Varchar'
	);	

	/**
	 * Set featured image
	 * 
	 * @var array
	 */
	private static $has_one = array(
		'FeaturedImage' => 'Image', 
		'RedirectPage' => 'SiteTree'
	);

	/**
	 * Set icon
	 * 
	 * @var string
	 */
	private static $icon = 'datacapture/images/capture-icon.png';

	/**
	 * Get CMS fields
	 * 
	 * @return FieldList
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Widgets');
		$fields->removeByName('Teasers');
		$fields->removeByName('Resources');

		$fields->removeFieldsFromTab(
			'Root.Main', 
			array('Banner', 'CustomHeaderImage')
		);

		$fields->addFieldToTab(
			'Root.Main', 
			UploadField::create('FeaturedImage', 'Featured Image')
				->setFolderName('DataCapture/Images/')
		);

		$fields->addFieldToTab(
			'Root.Form', 
			TextField::create('FormTitle', 'Title')
		);

		$fields->addFieldToTab(
			'Root.Form', 
			TextareaField::create('FormDescription', 'Description')
		);

		$fields->addFieldToTab(
			'Root.Form', 
			TextField::create('ListID', 'List ID')
		);

		$fields->addFieldToTab(
			'Root.Form', 
			TextField::create('MergeTag', 'Merge tag')
		);

		$fields->addFieldToTab(
			'Root.Form', 
			TextField::create('FormButtonTitle', 'Button Text')
		);

		$fields->addFieldToTab(
			'Root.Form', 
			TreedropdownField::create('RedirectPageID', 'Choose a thank you page', 'SiteTree')
		);		

		return $fields;
	}
}

/**
 * Controls the data capture page
 * 
 * @author Julius <julius@greenbrainer.com>
 * @copyright Copyright (c) 2015, Julius
 */
class DataCapturePage_Controller extends Page_Controller {

	/**
	 * Set allowed actions
	 * 
	 * @var array
	 */
	private static $allowed_actions = array(
		'Form'
	);

	/**
	 * Initialise the controller
	 */
	public function init() {
		parent::init();

		Requirements::customCSS(<<<CSS
			.st_facebook_custom, .st_twitter_custom, .st_linkedin_custom, .st_googleplus_custom {
				cursor: pointer;
			}
CSS
		);

		Requirements::javascript('http://w.sharethis.com/button/buttons.js');
		Requirements::customScript(<<<JS
			stLight.options({
				publisher: "df062f19-5c0a-49cd-aa6c-241a45fa6d96", 
				doNotHash: false, 
				doNotCopy: false, 
				hashAddressBar: false, 
				servicePopup: true
			});			
JS
		);		
	}

	/**
	 * Setup the form
	 *
	 * @return  Form
	 */
	public function Form() {
		$fields = new FieldList(
			TextField::create('Name', false, Cookie::get('DataCaptureFormName'))
				->setAttribute('placeholder', 'Full Name'),
			EmailField::create('Email', false, Cookie::get('DataCaptureFormEmail'))
				->setAttribute('placeholder', 'Email')
		);

		$formAction = FormAction::create('doCapture')
			->setTitle($this->FormButtonTitle);
		$formAction->useButtonTag = true;
		$formAction->addExtraClass('btn btn-default btn-submit');
		$actions = new FieldList($formAction);

		$required = new RequiredFields('Name', 'Email');

		$form = new Form($this, 'Form', $fields, $actions, $required);
		$form->addExtraClass('forms');
		$form->setTemplate('DataCaptureForm');

		return $form;		
	}

	/**
	 * Send to mailchimp api 
	 * 
	 * @param  Array  $data
	 * @param  Form   $form
	 * @return Form
	 */
	public function doCapture(Array $data, Form $form) {
		Cookie::set('DataCaptureFormName', $data['Name'], 365);
		Cookie::set('DataCaptureFormEmail', $data['Email'], 365);

		$settings = SiteConfig::current_site_config();

		$MailChimp = new \Drewm\MailChimp($settings->APIKey);

		$apiData = array(
			'id'                => $this->ListID,
			'email'             => array('email' => $data['Email']),
			'merge_vars'        => array(
									'Name' => $data['Name']
								),
			'double_optin'      => false,
			'update_existing'   => true,
			'replace_interests' => false,
			'send_welcome'      => false,
		);

		$result = $MailChimp->call('lists/subscribe', $apiData);

		$this->redirect($this->RedirectPage()->Link());
	}
}