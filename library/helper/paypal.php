<?php
  /**
   * paypal helper class
   * it is derived from paypal sdk , to which the copyright blongs
   * 
   * @author wade
   */

  class Helper_Paypal
  {
      /**
       * paypal api basic settings,
       * it will be used as query string
       *
       * @access private
       */
      private static $api_basic_settings = array(
        /**
        # API user: The user that is identified as making the call. you can
        # also use your own API username that you created on PayPal sandbox
        # or the PayPal live site
        */ 
        'USER' => 'littlemoment-facilitator_api1.hotmail.com',
       
        /**
        # API_password: The password associated with the API user
        # If you are using your own API username, enter the API password that
        # was generated by PayPal below
        # IMPORTANT - HAVING YOUR API PASSWORD INCLUDED IN THE MANNER IS NOT
        # SECURE, AND ITS ONLY BEING SHOWN THIS WAY FOR TESTING PURPOSES
        */
        'PWD' => '1374725665',

        /**
        # API_Signature:The Signature associated with the API user. which is generated by paypal.
        */
        'SIGNATURE' => 'AHGrYToaHRe4kc.EZFeV5zGoda6pASdsn4Q8MexRKiVHd-ZMptnCk.KN',

        /**
        # Third party Email address that you granted permission to make api call.
        */
        //'SUBJECT' => '',

        /**
        # Version: this is the API version in the request.
        # It is a mandatory parameter for each API request.
        # The only supported value at this time is 2.3
        */
        'VERSION' => '65.1',

        /**
        # transaction type: Sale or Authorization , and so on
        */
        'PAYMENTACTION' => 'Sale',
        
        /**
        # currency type
        */
        'CURRENCYCODE' => 'USD'
      );

      /**
       * paypal api other settings,
       * when curl function is called, it will be used.
       *
       * @access private
       */
      private static $api_other_settings = array(
        /**
        # request url: this is the server URL which you have to connect for submitting your API request.
        */
        'request_url' => 'https://api-3t.sandbox.paypal.com/nvp',

        /**
        # Define the PayPal URL. This is the URL that the buyer is
        # first sent to to authorize payment with their paypal account
        # change the URL depending if you are testing on the sandbox
        # or going to the live PayPal site
        # For the sandbox, the URL is
        # https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
        # For the live site, the URL is
        # https://www.paypal.com/webscr&cmd=_express-checkout&token=
        */
        'PAYPAL_URL' => 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token='
      );
      
      /**
       * execute
       *
       * @param string $medthod
       * @param array $param_list
       * @access public
       * @return array
       */
      public static function execute($method , $param_list)
      {
          return self::_hash_call($method , self::_toQueryString(array_merge(self::$api_basic_settings , $param_list)));
      }
      
      /**
       * get the api selectd setting value
       *
       * @param string $propname
       * @access public
       * @return mixed
       */
      public static function getPropValue($propname)
      {
          $temp_arr = array_merge(self::$api_other_settings , self::$api_basic_settings);
          foreach ($temp_arr as $key => $value)
          {
              if (strtolower($key) == $propname)  return $value;
          }
          return false;
      }

      /**
       * execute curl
       *
       * @param $methodName string
       * @param $nvpStr string
       * @access private
       * @return associative array or if curl failed , it will throw exception
       */
      private static function _hash_call($methodName , $nvpStr)
      {
          //setting the curl parameters.
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, self::$api_other_settings['request_url']);
          curl_setopt($ch, CURLOPT_VERBOSE, 1);

          //turning off the server and peer verification(TrustManager Concept).
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

          curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
          curl_setopt($ch, CURLOPT_POST, 1);

          /*
          //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
          //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
          if(USE_PROXY)
          curl_setopt ($ch, CURLOPT_PROXY, PROXY_HOST.":".PROXY_PORT); 
          */
      
          // query string in order to send by post method
          $nvpreq="METHOD=".urlencode($methodName).'&'.$nvpStr;
    
          //setting the nvpreq as POST FIELD to curl
          curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

          //getting response from server
          $response = curl_exec($ch);

          // whether it success?
          if (curl_errno($ch))
          {
              // throw exception 
              throw new Exception('curl failed: '.curl_errno($ch).' '.curl_error($ch));
          } 
          else
          {
              //closing the curl
              curl_close($ch);
          }

          //convrting NVPResponse to an Associative Array
          $nvpResArray=self::_deformatNVP($response);

          // return response data
          return $nvpResArray;
      }

      /**
       * this function can convert name value pair string to an associative array
       *
       * @param $nvpstr string
       * @access private
       * @return array
       */
      private static function _deformatNVP($nvpstr)
      {
          $intial=0;
          $nvpArray = array();
          while(strlen($nvpstr))
          {
              //postion of Key
              $keypos= strpos($nvpstr,'=');
              //position of value
              $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

              /*getting the Key and Value values and storing in a Associative Array*/
              $keyval=substr($nvpstr,$intial,$keypos);
              $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
              //decoding the respose
              $nvpArray[urldecode($keyval)] =urldecode( $valval);
              $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
         }
         return $nvpArray;
      }

      /**
       * convert associative array to query string
       * 
       * @param $param_list
       * @access private
       * @return string
       */
      private static function _toQueryString($param_list)
      {
          $query_str = '';
          $temp_array = array_merge(self::$api_basic_settings , $param_list);
          foreach ($temp_array as $key => $value)
          {
              $query_str .= $key.'='.urlencode($value).'&';
          }
          return rtrim($query_str , '&');
      }
  }
?>