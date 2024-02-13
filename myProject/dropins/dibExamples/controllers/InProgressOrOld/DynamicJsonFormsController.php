<?php

class DynamicFormsController extends Controller {
	
	public function getHtmlData ($containerName, $itemEventId='123', $clientData=array()) {
		$design = [
            [
                'type' => 'card',
                'caption' => 'Test 1',
                'children' => [
                    [
                        'id' => 'text1',
                        'type' => 'inputText', // if no id then it is taken from caption; if caption is already an id, then set to caption + next number
                        'caption' => 'text1',
                        'required' => '=', // = indicates that this attribute is set without a value in quotes; so 'required' and not 'required="true"'
                    ],
                    [
                        'id' => 'myDate',
                        'type' => 'inputDate',
                        'caption' => 'date1',
                    ],
                    [
                        'id' => 'myCheck',
                        'type' => 'checkbox',
                        'caption' => 'check1',
                    ],
                    [
                        'type' => 'card',
                        'caption' => 'Inside Card',
                        'children' => [
                            [
                                'id' => 'text2',
                                'type' => 'inputText', // if no id then it is taken from caption; if caption is already an id, then set to caption + next number
                                'caption' => 'text2',
                                'required' => '=', // = indicates that this attribute is set without a value in quotes; so 'required' and not 'required="true"'
                            ],
                            [
                                'id' => 'myDate2',
                                'type' => 'inputDate',
                                'caption' => 'date2',
                            ],
                        ]
                    ]
                ]
            ],
            [
                'type' => 'card',
                'children' => [
                    [
                        'id' => 'text3',
                        'type' => 'inputText', // if no id then it is taken from caption; if caption is already an id, then set to caption + next number
                        'caption' => 'text3',
                        'required' => '=', // = indicates that this attribute is set without a value in quotes; so 'required' and not 'required="true"'
                    ],
                    [
                        'id' => 'myDate3',
                        'type' => 'inputDate',
                        'caption' => 'date3',
                    ],
                    [
                        'id' => 'myCheck3',
                        'type' => 'checkbox',
                        'caption' => 'check3',
                    ],
                ]
            ]
        ];

        // Get component library to use
        DibApp::load('dibDynamicUI', 'DHtmlComponents.php', 'components');
        $components = DHtmlComponents::$defaultHtmlComponents;
  
        // Get html and dataTypes
        DibApp::load('dibDynamicUI', 'DDynamicUI.php', 'components');
        $cacheName = 'myform1';
        $html = DDynamicUI::convertToHtml($cacheName, $design, $components);

        if($html === false)
            return $this->invalidResult('Could not load form. Please contact a System Administrator.');

        // Get data and validate it
        $data = [
            'text1' => 'helloworld',
            'myDate3' => '2022-02-19',
            'myCheck3' => 1
        ];

        $result = DDynamicUI::validate('myform1', $data);
        if($result !== true)
            return $this->invalidResult($result);
        
        // Return either, or both of 'html' and 'data'
        $info = [
            'html'=>$html,
            'data'=>$data,
            'dataTypes'=>DDynamicUI::$dataTypes
        ];

		return $this->records($info);
	}

    public function getData ($containerName, $itemEventId='123', $clientData=array()) {
        
        // Return either or both of 'html' and 'design'
        $data = [];
        $info = [
            'data'=>$data
        ];

		return $this->records($info);
    }

	public function save ($containerName, $itemEventId, $clientData) {
        // handle server-side validation as well

	}

	public function delete ($containerName, $id=null) {
        /*
		$post = DibApp::jsonDecode($this->readInput(), FALSE);
		
		$result = Crud::delete('dib_calendar_item_grid', array('id'=>$post['EventId']));
		
		if($result !== true)
			return $this->invalidResult('Could not delete event. Please contact the System Administrator.');
        */
		return $this->validResult(NULL);
	}

	
}