<form action="home/batch_delete" method="post" class="tbform">

<div class="toolbar">
	<button name="delete" class="btn btn-sm btn-danger">
		<span class="fa fa-trash"></span> Hapus pesan terpilih
	</button>
</div>

<div class="table-data">
	<div class="trh">
		<div class="th"><input type="checkbox" name="checkid" value="all" class="allcheck"></div>
		<div class="th">Untuk</div>
		<div class="th">Subject</div>
		<div class="th">Tanggal</div>
	</div>
	<?php
	$n = 0;
	foreach($query as $row){
		$tgl = $this->def->indo_date($row['tgl']);
		$name = $this->def->get_admin($row['username']);

		$to = "";
		$get_to = $this->mail->get_receiver($row['id']);
		foreach($get_to as $gt){
			$to .= $gt['username'].", ";
		}
		$to = substr($to, 0, -2);

		$adclass = "";
		$ctrl = $this->mail->get_control($row['id']);
		if($ctrl > 1){
			continue;
		}

		$n++;

		echo "
		<a href='outbox/view/$row[id]' class='tr $adclass'>
			<div class='td'><input type='checkbox' name='checkid[]' class='checkid' value='ch_$row[id]'></div>
			<div class='td'>$to</div>
			<div class='td'>$row[subject]</div>
			<div class='td'>$tgl</div>
		</a>
		";
	}

	if($n == 0){
		echo "
		<div class='tr'>
			<div>
				Kotak keluar kosong
			</div>
		</div>
		";
	}
	?>
</table>
</div>
</form>

<script>
	var refresh = setInterval(function(){
		location.reload();
	},180000);
</script>