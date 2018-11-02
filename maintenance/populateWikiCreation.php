<?php
/**
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc.,
* 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
* http://www.gnu.org/copyleft/gpl.html
*
* @file
* @ingroup Maintenance
* @author Southparkfan
* @author John Lewis
* @author Paladox
* @version 1.0
*/

require_once( __DIR__ . '/../../../maintenance/Maintenance.php' );

class PopulateWikiCreation extends Maintenance {
	public function __construct() {
		parent::__construct();
		 $this->mDescription = "Populates wiki_creation column in cw_wikis table";
	}

	public function execute() {
		global $wgCreateWikiDatabase;

		$dbw = wfGetDB( DB_MASTER, [], $wgCreateWikiDatabase );

		$res = $dbw->select(
			'cw_wikis',
			'*',
			array(),
			__METHOD__
		);

		if ( !$res || !is_object( $res ) ) {
			throw new MWException( '$res was not set to a valid array.' );
		}

		foreach ( $res as $row ) {
			$DBname = $row->wiki_dbname;

			$dbw->selectDB( $wgCreateWikiDatabase );

			$res = $dbr->selectRow(		
				'logging',		
				'log_timestamp',		
				array(		
					'log_action' => 'createwiki',		
					'log_params' => serialize( array( '4::wiki' => $dbname ) )		
				),		
				__METHOD__,		
				array( // Sometimes a wiki might have been created multiple times.		
					'ORDER BY' => 'log_timestamp DESC'		
				)		
			);

			$dbw->insert( 'cw_wikis',
				[
					'wiki_creation' => $res->log_timestamp,
				],
				__METHOD__
			);

			$this->output( "Inserted {$res->log_timestamp} into wiki_creation column for db {$DBname}\n")
		}
	}
}

$maintClass = 'PopulateWikiCreation';
require_once RUN_MAINTENANCE_IF_MAIN;