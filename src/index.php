<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Tipsy\Tipsy;
Tipsy::init();

t::config('../src/config.ini');

if (getenv('HEROKU')) {
	t::config('../src/config.heroku.ini');
}

$envdb = getenv('CLEARDB_DATABASE_URL') ? getenv('CLEARDB_DATABASE_URL') : getenv('DATABASE_URL');

if ($envdb) {
	t::config(['db' => ['url' => $envdb]]);
} elseif (file_exists('../src/config.db.ini')) {
	t::config('../src/config.db.ini');
}

class Db extends \Tipsy\Db {
	public static function mysqlToPgsql($query) {
		// replace backticks
		$query = str_replace('`','"', $query);

		// replace add single quotes to interval statements
		$query = preg_replace('/(interval) ([0-9]+) ([a-z]+)/i','\\1 \'\\2 \\3\'', $query);

		return $query;
	}

	public function query($query, $args = []) {
		if (!$query) {
			throw new Exception('Query is emtpy');
		}
		$query = self::mysqlToPgsql($query);
		if (!$query) {
			throw new Exception('mysqlToPgsql Query is emtpy');
		}
		return parent::query($query, $args);
	}

	public function exec($query) {
		return parent::exec(self::mysqlToPgsql($query));
	}
}

if (strpos($envdb, 'postgres') !== false) {
	t::service('Db');
}

t::service('Tipsy\Resource/Link', [
	put => function($link) {
		if ($count > $this->tipsy()->config()['data']['max']) {
			if ($this->tipsy()->db()->driver() == 'pgsql') {
				$q = 'delete from "link" where ctid in (select ctid FROM "link" order by date limit '.($count - $this->tipsy()->config()['data']['max']).')';
			} else {
				$q = 'delete from `link` order by id asc limit '.($count - $this->tipsy()->config()['data']['max']);
			}
			$this->tipsy()->db()->exec($q);
		}
	},
	find => function($id) {
		$link = $this->q('select * from link where permalink=?', $id)->get(0);
		if (!$link) {
			$link = $this->q('select * from link where id=?', $this->decode($id))->get(0);
		}
		return $link;
	},
	exports => function() {
		$ret = [
			'permalink' => $this->permalink,
			'date' => $this->date,
			'hits' => intval($this->hits),
			'url' => $this->url,
			'id' => $this->encode($this->id)
		];

		return $ret;
	},
	encode => function($val) {
		if ($val) {
			$str = '';
			do {
				$i = $val % $this->_base;
				$str = $this->_chars[$i] . $str;
				$val = ($val - $i) / $this->_base;
			} while($val > 0);
			return $str;
		} else {
			return '';
		}
	},
	decode => function($str) {
		if ($str) {
			$len = strlen($str);
			$val = 0;
			$arr = array_flip(str_split($this->_chars));
			for($i = 0; $i < $len; ++$i) {
				$val += (isset($arr[$str[$i]]) ? $arr[$str[$i]] : 0) * pow($this->_base, $len-$i-1);
			}
			return $val;
		} else {
			return '';
		}
	},
	clean => function($id) {
		return preg_replace('/[^0-9a-z\-_]/i', '', $id);
	},
	check => function() {
		$link = $this->q('select * from link where permalink=?', $id)->get(0);
		return ($link && $link->id) ? false : true;
	},

	_chars => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
	_base => '62',

	_id => 'id',
	_table => 'link'
]);

t::router()
	->when(':id', function($View, $Link, $Params) {
		$id = $Link->clean($Params->id);

		if ($id) {
			$l = $Link->find($id);
		} else {
			$View->display('index');
			exit;
		}

		if ($l->id) {
			$l->hits++;
			$l->save();
			header('Location: '.(strpos($l->url, '://') === false ? 'http://' : '').$l->url);
		} else {
			header('Location: /');
		}
	})
	->when('link/:id', function($View, $Link, $Params) {
		$id = $Link->clean($Params->id);

		if ($id) {
			$l = $Link->find($id);
		}

		if ($l->id) {
			echo $l->json();
		} else {
			http_response_code(404);
		}
	})
	->when('view/:id', function($View, $Link, $Params) {
		$id = $Link->clean($Params->id);

		if ($id) {
			$l = $Link->find($id);
		}

		if ($l->id) {
			$View->display('index');
		} else {
			header('Location: /');
		}
	})
	->post('submit', function($Link, $Request) {
		$Request->url = trim($Request->url);

		if (!$Request->url || !strpos($Request->url, '.')) {
			echo json_encode([status => false, message => 'URL is invalid']);
			exit;
		}

		if (strpos($Request->url, '://') === false) {
			$Request->url = 'http://'.$Request->url;
		}

		$find = [
			'/ |\+/i',
			'/[^a-z0-9\-_\.]/i'
		];
		$replace = [
			'-',
			''
		];

		$Request->permalink = trim(preg_replace($find, $replace, $Request->permalink));

		if ($Request->permalink && !$Link->check($Request->permalink)) {
			echo json_encode([status => false, message => 'Permalink is taken']);
			exit;
		}
		$l = $Link->create([
			date => date('Y-m-d H:i:s'),
			url => $Request->url,
			permalink => $Request->permalink ? $Request->permalink : null
		]);

		echo $l->json();
	})
	->otherwise(function($View, $Link, $Request) {
		$View->display('index');
	});

t::run();


