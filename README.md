# phpMTGO
A PHP magic the gathering online (MTGO) parser. 

Currently Parse the auto generated logs from the MTGO client.

To enable auto generated draft logs on the new client go
Account-> Game History-> "Auto save draft logs".

To use the file all you need to do is:
```
$path_to_file = 'sample_draft.txt';
$draft = new MTGODraftFile( $path_to_file );
$result = $draft->results;
```
There will be 3 packs in $result['packs']
and
15 'picks' in each pack.

The returned array will be as follows:
```
array(
	'event_id' => '9488616',
	'date' => '10/03/2016',
	'time' => '15:48',
	'active_player' => 'Player1',
	'players' => array(
		(int) 0 => 'Player1',
		(int) 1 => 'Player2',
		(int) 2 => 'Player3',
		(int) 3 => 'Player4',
		(int) 4 => 'Player5',
		(int) 5 => 'Player6',
		(int) 6 => 'Player7',
		(int) 7 => 'Player8'
	),
	'packs' => array(
		(int) 0 => array(
			'set' => 'OGW',
			'picks' => array(
				(int) 0 => array(
					'pack' => '1',
					'pick' => '1',
					'picked_card' => 'Oblivion Strike',
					'cards' => array(
						(int) 0 => 'Blinding Drone',
						(int) 1 => 'Kozilek's Pathfinder',
						(int) 2 => 'Spawnbinder Mage',
						(int) 3 => 'Oblivion Strike',
						(int) 4 => 'Kozilek's Shrieker',
						(int) 5 => 'Lead by Example',
						(int) 6 => 'Untamed Hunger',
						(int) 7 => 'Grasp of Darkness',
						(int) 8 => 'Umara Entangler',
						(int) 9 => 'Akoum Flameseeker',
						(int) 10 => 'Grip of the Roil',
						(int) 11 => 'Baloth Null',
						(int) 12 => 'Wall of Resurgence',
						(int) 13 => 'Hissing Quagmire',
						(int) 14 => 'Island'
					)
				)
		  )
	  )
	)
)
```

