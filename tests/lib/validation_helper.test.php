<?php

use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for validation_helper.php
 */
class ValidationHelperTestCase extends LucidFrameTestCase
{
    public function testDateValidation()
    {
        $validations = array();
        $values = array(
            1 => array('31/12/2014', 'd/m/y'),
            2 => array('12/31/2014', 'm/d/y'),
            3 => array('2014-12-31', ''),
            4 => array('28.2.2014', 'd.m.y'),
            5 => array('02.28.2014', 'm.d.y')
        );
        foreach ($values as $key => $val) {
            $validations['txtDate'.$key] = array(
                'caption'   => 'Date '.$key,
                'value'     => $val[0],
                'rules'     => array('date'),
                'dateFormat'=> $val[1],
            );
        }
        $this->assertTrue(validation_check($validations));
    }

    public function testTimeValidation()
    {
        $validations = array();
        $values = array(
            1 => '13:59',
            2 => '13:59:59',
            3 => '1:00pm',
            4 => '01:59:59 PM',
            5 => '04:00',
            6 => '05:00 AM',
            7 => '12:59 PM'
        );
        foreach ($values as $key => $val) {
            $validations['txtTime'.$key] = array(
                'caption'   => 'Time '.$key,
                'value'     => $val,
                'rules'     => array('time'),
            );
        }
        $this->assertTrue(validation_check($validations));

        $validations = array();
        $values = array(
            3 => '1:00pm',
            4 => '01:59:59 PM',
            6 => '05:00 AM',
            7 => '12:59 PM'
        );
        foreach ($values as $key => $val) {
            $validations['txtTime'.$key] = array(
                'caption'   => 'Time '.$key,
                'value'     => $val,
                'rules'     => array('time'),
                'timeFormat'=> '12'
            );
        }
        $this->assertTrue(validation_check($validations));

        $validations = array();
        $values = array(
            1 => '13:59',
            2 => '13:59:59',
            3 => '04:00',
            4 => '23:59',
        );
        foreach ($values as $key => $val) {
            $validations['txtTime'.$key] = array(
                'caption'   => 'Time '.$key,
                'value'     => $val,
                'rules'     => array('time'),
                'timeFormat'=> '24'
            );
        }
        $this->assertTrue(validation_check($validations));
    }

    public function testDateTimeValidation()
    {
        $validations = array();
        $values = array(
            1 => array('31/12/2014 13:59', 'd/m/y'),
            2 => array('12/31/2014 13:59:59', 'm/d/y'),
            3 => array('2014-12-31 1:00pm', 'y-m-d'),
            4 => array('28.2.2014 01:59:59 PM', 'd.m.y'),
            5 => array('02.28.2014 11:59 am', 'm.d.y')
        );
        foreach ($values as $key => $val) {
            $validations['txtDateTime'.$key] = array(
                'caption'   => 'DateTime '.$key,
                'value'     => $val[0],
                'rules'     => array('datetime'),
                'dateFormat'=> $val[1]
            );
        }
        $this->assertTrue(validation_check($validations));

        $validations = array();
        $values = array(
            1 => array('31/12/2014 13:59', 'd/m/y'),
            2 => array('12/31/2014 13:59:59', 'm/d/y'),
            3 => array('12/31/2014 02:00:00', 'm/d/y'),
        );
        foreach ($values as $key => $val) {
            $validations['txtDateTime'.$key] = array(
                'caption'   => 'DateTime '.$key,
                'value'     => $val[0],
                'rules'     => array('datetime'),
                'dateFormat'=> $val[1],
                'timeFormat'=> '24'
            );
        }
        $this->assertTrue(validation_check($validations));

        $validations = array();
        $values = array(
            1 => array('2014-12-31 1:00pm', 'y-m-d'),
            2 => array('28.2.2014 01:59:59 PM', 'd.m.y'),
            3 => array('02.28.2014 11:59 am', 'm.d.y')
        );
        foreach ($values as $key => $val) {
            $validations['txtDateTime'.$key] = array(
                'caption'   => 'DateTime '.$key,
                'value'     => $val[0],
                'rules'     => array('datetime'),
                'dateFormat'=> $val[1],
                'timeFormat'=> '12'
            );
        }
        $this->assertTrue(validation_check($validations));
    }

    public function testUrlSuccessValidation()
    {
        $values = array(
            'example.com.sg',
            'example.audio',
            'phplucidframe.com',
            'http://phplucidframe.com',
            'https://phplucidframe.com',
            'http://www.phplucidframe.com',
            'https://www.phplucidframe.com',
            'https://phplucidframe.com/',
            'https://phplucidframe.com/',
            'https://github.com/phplucidframe/console-table',
            'https://github.com/phplucidframe/phplucidframe/blob/master/index.php#L15',
            'https://www.udemy.com/teaching/?ref=teach_header',
            'https://example.com/d/1V5xSQVeuUCv5o6f8nfjZEf-swNFRg04jEGNZ9Sny7f/edit?usp=sharing',
            'https://www.example.com/landing-page?utm_source=google&utm_medium=email&utm_campaign=march2014',
        );
        foreach ($values as $key => $url) {
            $validations = array();
            $validations['txtUrl' . $key] = array(
                'caption'   => 'URL ' . $key,
                'value'     => $url,
                'rules'     => array('url'),
            );
            $this->assertTrue(validation_check($validations));
        }
    }

    public function testUrlFailedValidation()
    {
        $values = array(
            'abc',
            'hello@phplucidframe.com',
            'http:/phplucidframe.com',
            'http://w.phplucidframe.com',
            'https://ww.phplucidframe.com',
            'https://wwww.phplucidframe.com',
            'https://wwwww.phplucidframe.com',
            'https:// phplucidframe.com',
        );
        foreach ($values as $key => $url) {
            $validations = array();
            $validations['txtUrl' . $key] = array(
                'caption'   => 'URL ' . $key,
                'value'     => $url,
                'rules'     => array('url'),
            );
            $this->assertFalse(validation_check($validations));
        }
    }

    public function testMyanmarPhoneValidation()
    {
        $phones = [
            # old mobile number start with 095
            '095123456',
            '095654321',
            # MPT start with 092, 094
            '09212345678',
            '09457746673',
            '09255557777',
            # Ooredoo start with 098 or 099.
            '09812345678',
            '09912345678',
            '09970000234',
            # Telenor/ATOM start with 097
            '09760100820',
            '09770707888',
            '09790009000',
            # Mytel start with 096
            '09660909909',
            '09690000966',
            # Landline start with 01, 02, etc.
            '01371848',
            '012317722',
            '018610322',
            '012317777',
            '012399106',
        ];

        foreach ($phones as $key => $ph) {
            $validations = [
                'txtPhone_' . $key => [
                    'caption'   => 'Phone ' . $key,
                    'value'     => $ph,
                    'rules'     => ['mmPhone'],
                ]
            ];
            $this->assertTrue(validation_check($validations));

            $ph = substr($ph, 1); // cut the first 0

            foreach (['95', '+95', '(+95)', '(95)'] as $countryCode) {
                $val = $countryCode . $ph;
                $validations = [
                    'txtPhone_' . $key => [
                        'caption'   => 'Phone ' . $key,
                        'value'     => $val,
                        'rules'     => ['mmPhone'],
                    ]
                ];
                $this->assertTrue(validation_check($validations));
            }
        }
    }
}
