<?php
defined('BASEPATH') or exit('No direct script access allowed');

class App_gateway
{
    /**
     * Hold Codeigniter instance
     * @var object
     */
    protected $ci;
    /**
     * Stores the gateway id
     * @var alphanumeric
     */
    protected $id = '';
    /**
     * Gateway name
     * @var mixed
     */
    protected $name = '';
    /**
     * All gateway settings
     * @var array
     */
    protected $settings = array();

    /**
     * Must be called from the main gateway class that extends this class
     * @param alphanumeric $id   Gateway id - required
     * @param mixed $name Gateway name
     */
    public function __construct()
    {
        $this->ci =& get_instance();
    }

    public function initMode($modes)
    {
        /**
         * Try to add the options if the gateway is first time added or is options page in admin area
         * May happen there is new options added so let the script re-check
         * add_option will not re-add the option if already exists
         */
        if (!$this->isInitialized() || $this->isOptionsPage()) {
            foreach ($this->settings as $option) {
                $val = isset($option['default_value']) ? $option['default_value'] : '';
                add_option('paymentmethod_'. $this->getId() . '_' . $option['name'], $val, 0);
            }
            add_option('paymentmethod_'. $this->getId() . '_initialized', 1);
        }

        /**
         * Inject the mode with other modes with action hook
         */
        $modes[] = array(
            'id' => $this->getId(),
            'name' => $this->getSetting('label'),
            'description' => '',
            'selected_by_default'=>$this->getSetting('default_selected'),
            'active' => $this->getSetting('active')
        );
        
        return $modes;
    }

    /**
     * Set gateway name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Return gateway name
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set gateway id
     * @param string alphanumeric $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Return gateway id
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set gateway settings
     * @param array $settings
     */
    public function setSettings($settings)
    {

        /**
         * Append on top the dafault settings active and label
         */
        array_unshift(
            $settings,
            array(
                'name'=>'active',
                'type'=>'yes_no',
                'default_value'=>0,
                'label'=>'settings_paymentmethod_active',
            )
            //     ),
            // array(
            //     'name'=>'label',
            //     'default_value'=>$this->getName(),
            //     'label'=>'settings_paymentmethod_mode_label',
            //     )
        );

        /**
         * Add on bottom default selected on invoice setting
         */
        // $settings[] = array(
        //     'name' => 'default_selected',
        //     'type' => 'yes_no',
        //     'default_value' => 1,
        //     'label' => 'settings_paymentmethod_default_selected_on_invoice'
        //     );

        $this->settings = $settings;
    }

    /**
     * Add payment based on payment method
     * @param array $data payment data
     * Params
     * amount - Required
     * invoiceid - Required
     * transactionid - Optional but recommended
     * paymentmethod - Optional
     * note - Optional
     */
    public function addPayment($data, $type = '') {
        $sess_id = $this->ci->session->userdata('brand_id');
        if(!empty($sess_id)) {
            $data['paymentmode']   = $this->getId();
            $this->ci->load->model('payments_model');
            return $this->ci->payments_model->add($data, $type);
        } else {
            //for registration subscription
            $data['paymentmode']   = $this->getId();
            $this->ci->load->model('payments_model');
            $this->ci->payments_model->add($data, $type);
        }
    }

    /**
     * Get all gateway settings
     * @param  boolean $formatted Should the setting be formated like is on db or like it passed from the settings
     * @return array
     */
    public function getSettings($formatted = true)
    {
        $settings = $this->settings;
        if ($formatted) {
            foreach ($settings as $key => $option) {

                $settings[$key]['name'] = 'paymentmethod_' . $this->getId() . '_' . $option['name'];
            }
        }

        return $settings;
    }

    /**
     * Return single setting passed by name
     * @param  mixed $name Option name
     * @return string
     */
    public function getSetting($name, $type = "")
    {
        /**
        * Modified By : Vaidehi
        * Dt : 03/16/2018
        * to get options for subcription
        */
        $CI =& get_instance();
        if( $CI->session->userdata('type')== "subscription") {
            return trim(get_option('paymentmethod_'. $this->getId() . '_' .$name));
        } else {
            return trim(get_brand_option('paymentmethod_'. $this->getId() . '_' .$name));            
        }
    }

    /**
    * Added By : Vaidehi
    * Dt : 03/29/2018
    * to get options for transaction charge
    */
    /**
     * Return single setting passed by name
     * @param  mixed $name Option name
     * @return string
     */
    public function getAppSetting($name, $type = "")
    {
        return trim(get_option('paymentmethod_'. $this->getId() . '_' .$name));
    }

    /**
     * Decrypt setting value
     * @return string
     */
    public function decryptSetting($name, $type = "")
    {
        return trim($this->ci->encryption->decrypt($this->getSetting($name, $type = "")));
    }

    /**
    * Added By : Vaidehi
    * Dt : 03/29/2018
    * to get options for transaction charge
    */
    /**
     * Decrypt setting value
     * @return string
     */
    public function decryptAppSetting($name, $type = "")
    {
        return trim($this->ci->encryption->decrypt($this->getAppSetting($name, $type = "")));
    }

    /**
     * Check if payment gateway is initialized and options are added into database
     * @return boolean
     */
    protected function isInitialized()
    {
        return $this->getSetting('initialized') == '' ? false : true;
    }

    /**
     * Check if is settings page in admin area
     * @return boolean
     */
    private function isOptionsPage()
    {
        return $this->ci->input->get('group') == 'online_payment_modes' && $this->ci->uri->segment(2) == 'settings';
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_id()
    {
        return $this->getId();
    }

    /**
     * @deprecated
     * @return array
     */
    public function get_settings()
    {
        return $this->getSettings();
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_name()
    {
        return $this->getName();
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_setting_value($name)
    {
        return $this->getSetting($name);
    }
}
