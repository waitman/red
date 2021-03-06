<?php /** @file */

require_once('include/permissions.php');

function find_upstream_directory($dirmode) {
	return '';
}


function sync_directories($dirmode) {

	if($dirmode == DIRECTORY_MODE_STANDALONE || $dirmode == DIRECTORY_MODE_NORMAL)
		return;

	$r = q("select * from site where (site_flags & %d) and site_url != '%s'",
		intval(DIRECTORY_MODE_PRIMARY),
		dbesc(z_root())
	);

	// If there are no directory servers, setup the fallback master

	if((! $r) && (z_root() != DIRECTORY_FALLBACK_MASTER)) {
		$r = array(
			'site_url' => DIRECTORY_FALLBACK_MASTER,
			'site_flags' => DIRECTORY_MODE_PRIMARY,
			'site_update' => '0000-00-00 00:00:00', 
			'site_directory' => DIRECTORY_FALLBACK_MASTER . '/dirsearch'
		);
		$x = q("insert into site ( site_url, site_flags, site_update, site_directory )
			values ( '%s', %d', '%s', '%s' ) ",
			dbesc($r[0]['site_url']),
			intval($r[0]['site_flags']),
			dbesc($r[0]['site_update']),
			dbesc($r[0]['site_directory'])
		);

	} 






}







function syncdirs($uid) {

	logger('syncdirs', LOGGER_DEBUG);

	$p = q("select channel.channel_hash, channel_timezone, profile.* from profile left join channel on channel_id = uid where uid = %d and is_default = 1",
		intval($uid)
	);

	$profile = array();

	if($p) {
		$hash = $p[0]['channel_hash'];

		$profile['description'] = $p[0]['pdesc'];
		$profile['birthday']    = $p[0]['dob'];
		if($age = age($p[0]['dob'],$p[0]['channel_timezone'],''))  
			$profile['age'] = $age;

		$profile['gender']      = $p[0]['gender'];
		$profile['marital']     = $p[0]['marital'];
		$profile['sexual']      = $p[0]['sexual'];
		$profile['locale']      = $p[0]['locality'];
		$profile['region']      = $p[0]['region'];
		$profile['postcode']    = $p[0]['postal_code'];
		$profile['country']     = $p[0]['country_name'];
		if($p[0]['keywords']) {
			$tags = array();
			$k = explode(' ',$p[0]['keywords']);
			if($k)
				foreach($k as $kk)
					if(trim($kk))
						$tags[] = trim($kk);
			if($tags)
				$profile['keywords'] = $tags;
		}

		$hidden = (1 - intval($p[0]['publish']));

		logger('hidden: ' . $hidden);

		$r = q("select xchan_flags from xchan where xchan_hash = '%s' limit 1",
			dbesc($p[0]['channel_hash'])
		);

		// Be careful - XCHAN_FLAGS_HIDDEN should evaluate to 1
		if(($r[0]['xchan_flags'] & XCHAN_FLAGS_HIDDEN) != $hidden)
			$new_flags = $r[0]['xchan_flags'] ^ XCHAN_FLAGS_HIDDEN;
		else
			$new_flags = $r[0]['xchan_flags'];
		
		if($new_flags != $r[0]['xchan_flags']) {			

			$r = q("update xchan set xchan_flags = %d  where xchan_hash = '%s' limit 1",
				intval($new_flags),
				dbesc($p[0]['channel_hash'])
			);

		}


		if(perm_is_allowed($uid,'','view_profile')) {
			import_directory_profile($hash,$profile);

		}
		else {
			// they may have made it private
			$r = q("delete from xprof where xprof_hash = '%s' limit 1",
				dbesc($hash)
			);
			$r = q("delete from xtag where xtag_hash = '%s' limit 1",
				dbesc($hash)
			);
		}
	}

	// TODO send refresh zots to downstream directory servers
}
	
