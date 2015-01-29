<?php

date_default_timezone_set('America/Los_Angeles');

require_once __DIR__ . '/../vendor/autoload.php';

$tl = new Tipsy\Tipsy;

$tl->config('../src/config.ini');
if (file_exists('../src/config.db.ini')) {
	$tl->config('../src/config.db.ini');	
}

$tl->service('Tipsy\Resource/Link', [
	del => function() {
		$this->delete();
	},
	byPermalink => function($id) {
		return $this->q('select * from link where permalink=?', $id)->get(0);
	},
	exports => function() {
		$ret = [
			'permalink' => $this->permalink,
			'date' => $this->date,
			'hits' => $this->hits,
			'url' => $this->url
		];

		return $ret;
	},
	_id => 'id',
	_table => 'link'
]);

$tl->router()
	->when('/:id', function($View, $Link, $Params) {
		
		$id = preg_replace('/[^0-9a-z\-_]/i', '', $Params->id);

		if ($id) {
			$l = $Link->byPermalink($Params->id);
		} else {
			$View->display('index');
			exit;
		}

		if ($l->id) {
			$l->hits++;
			$l->save();
			header('Location: '.$l->url);
		} else {
			header('Location: /');
		}
	})
	->otherwise(function($View, $Link, $Request) {
		$View->display('index');
	});

$tl->start();

