<?php

//=====================================================================
// Outils de debug et prévention des accidents
//

// Nb de lignes à loger lors d'une erreur php (ou d'un warning ou d'une notice etc)
// Particulièrement utile pour les erreurs dans les fichiers de cache SPIP, qui sont temporaires
// 7 par défaut, 0 pour ne pas loger d'extrait du source en erreur
define ('_DEBUG_ERREUR_NB_LIGNE_EXTRAIT', 7);

function nobug_trace ($chaine, $rem="") {
	echo "$rem:<pre>";
	var_dump($chaine);
	echo "</pre>";
}

// logs toujours activés, quelque soit le niveau de log sauf _LOG_HS
function nobug_log ($chaine, $filename='_nobug', $avec_stack=false) {
	if ($avec_stack) {
		$chaine .= "\n".nobug_get_stack();
	};
	spip_log ($chaine, "$filename."._LOG_CRITIQUE);
}

// asserts logés
function nobug_assert ($test, $message, $filename='assert') {
static $triggered=false;
	if (!$test and !$triggered) {
		$triggered=true;
		nobug_log ("=================================================\n$message\nGET=".print_r($_GET,1), $filename, true);
	};
	return $test; // permet de faire par exemple ' or exit;'
}

function nobug_get_stack () {
	ob_start();
	debug_print_backtrace();
	$stack = ob_get_contents();
	ob_end_clean();
	return $stack;
};

function nobug_date($date,$msg="")
{
    $an=$mois=$jour=$s=0;
	sscanf ($date, "%4d-%2d-%2d%s",$an,$mois,$jour,$s);
	nobug_assert (($an>=1998 and $an<= (intval(date("Y"))+1) and $mois>0 and $mois<13 and $jour>0 and $jour<32), $msg."(date=$date)(an=$an)(mois=$mois)(jour=$jour)<br>");
}

/**
 * Une fonction récursive pour joliment afficher #ENV, #GET, #SESSION...
 *    en squelette : [(#ENV*|nobug_tableau)], [(#GET|nobug_tableau)], [(#SESSION|nobug_tableau)]
 *    ou encore [(#ARRAY{0,1, a,#SESSION, 1,#ARRAY{x,y}}|nobug_tableau)]
 *
 * @param string|array $env
 *    si une string est passée elle doit être le serialize d'un array 
 * 
 * @param bool $bel
 * 		demande un formattage html avec css, sinon c'est du texte
 * 
 * @return string
 *    une chaîne html affichant une <table>
 * 
 * @example :
 * 		[(#GET|nobug_tableau)]
* 
* @d'aprés bel_env : contrib.spip.net/Astuces-longues-pour-SPIP
* 
**/
function nobug_tableau($env, $bel=true) {
	$env = str_replace(array('&quot;', '&#039;'), array('"', '\''), $env);
	if (is_array($env_tab = @unserialize($env))) {
		$env = $env_tab;
	}

	if (!is_array($env)) {
		return "(not array) ".print_r($env,1);
	}
	$style=$res="";
	if ($bel) {
		$style = " style='border:1px solid #ddd;'";
		$res = "<table style='border-collapse:collapse;'>\n";
	};

	foreach ($env as $nom => $val) {
		if (is_array($val) || is_array(@unserialize($val))) {
			$val = nobug_tableau($val, $bel);
		}
		else {
			$val = entites_html($val);
		}
		if ($bel)
			$res .= "<tr>\n<td$style><strong>". entites_html($nom).
				   "&nbsp;:&nbsp;</strong></td><td$style>" .$val. "</td>\n</tr>\n";
		else
			$res .= entites_html($nom)."=>".$val."\n";
	}
	if ($bel)
		$res .= "</table>";
	$res .= "\n";
	return $res;
}


// TODO : Utiliser set_error_handler pour les warnings et notices
// cf http://php.net/manual/fr/function.set-error-handler.php
// voir aussi ce que peut faire set_exception_handler
// http://php.net/manual/fr/function.set-exception-handler.php
if (!function_exists('nobug_handle_error'))
{
	function nobug_handle_error() {
		$last_error = error_get_last();
		
		if (!$last_error)
			return;

		$file = $last_error['file'];
		$line = $last_error['line'];

		// SPIP génère des warning a gogo en ligne 160 SPIP2.1 pour des unlink(tmp/cache/chemin.txt.150423698051368adb8c76d0.61673793): No such file or directory
		if (strpos( $last_error['file'], 'flock.php')
			or strpos( $last_error['file'], 'textwheel')) // textwheel génère trop d'avertissements aussi
			return;

		switch ($type = $last_error['type']) {
			case E_ERROR :
				$logfile = "_php_error";
				break;
			
			case E_WARNING :
				$logfile = "_php_warning";
				break;
				
			case E_NOTICE :
				$logfile = "_php_notice";
				break;
				
			default :
				$logfile = "_php_other_error";
				break;
		};

		$tobeloged = '';
		if (_DEBUG_ERREUR_NB_LIGNE_EXTRAIT) {
			$i_line = 1;
			$fh = @fopen($file, 'r');
			if (!$fh)
				nobug_log ("impossible d'ouvrir $file pour lire la source de l'erreur");
			else
				while (($buffer = fgets($fh)) !== FALSE) {
					if ($i_line == $line) {
						$tobeloged .= ">>> ".$buffer;
						if (preg_match("/[^\s]@/", $buffer) !== false) // les devs sont au courant... on relève pas
							return;
					}
					elseif (($i_line >= $line-_DEBUG_ERREUR_NB_LIGNE_EXTRAIT) and ($i_line <= $line+_DEBUG_ERREUR_NB_LIGNE_EXTRAIT))
						$tobeloged .= $buffer;
					elseif ($i_line > $line+7)
						break;
					$i_line++;
				};
		};

		if (($type == E_ERROR)
			or (strpos($file, '/paeco/')!==false)
			or (strpos($file, '/toscribus/')!==false))
			// Inutile d'insérer la stack ici car php est sorti de la stack now
			nobug_log (print_r($last_error, true).$tobeloged, $logfile);
		else
			nobug_log (print_r($last_error, true).$tobeloged, "spip".$logfile);
	}
	register_shutdown_function('nobug_handle_error');
};

function nobug_log_facteur ($facteur, $str='') {
	$facteur_log = clone $facteur;
	$facteur_log->Body = 'HTML (non logé)';
	$facteur_log->AltBody = 'txt (non logé)';
	nobug_assert ($facteur_log != $facteur, 'nobug_log_facteur débodité == facteur !!'.print_r($facteur,1));
	nobug_log ('facteur pour $str = '.print_r($facteur_log,1), "_facteur");
}
