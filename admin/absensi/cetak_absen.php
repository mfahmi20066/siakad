<?php
require_once '../../config/session.php';
require_once '../../config/koneksi.php';
cekAdmin();

$bulan      = (int)($_GET['bulan'] ?? date('m'));
$tahun      = $_GET['tahun'] ?? date('Y');
$id_kelas   = $_GET['id_kelas'] ?? '';
$id_mapel   = $_GET['id_mapel'] ?? '';
$bulan_nama = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];

$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas WHERE status='aktif' ORDER BY nama_kelas");
$q_mapel = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE status='aktif' ORDER BY nama_mapel");

$data_absen = []; $data_siswa = []; $nama_kelas = '-'; $nama_mapel = '-';

if ($id_kelas) {
    $rk = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM kelas WHERE id='$id_kelas'"));
    $nama_kelas = $rk['nama_kelas'] ?? '-';
    if ($id_mapel) {
        $rm = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE id='$id_mapel'"));
        $nama_mapel = $rm['nama_mapel'] ?? '-';
    }
    $rs = mysqli_query($koneksi, "SELECT * FROM siswa WHERE kelas_id='$id_kelas' ORDER BY nama_lengkap");
    while ($s = mysqli_fetch_assoc($rs)) $data_siswa[] = $s;
    $wm = $id_mapel ? "AND a.mapel_id='$id_mapel'" : "";
    $ra = mysqli_query($koneksi, "SELECT a.*, s.nama_lengkap, s.nis FROM absensi a JOIN siswa s ON a.siswa_id=s.id WHERE s.kelas_id='$id_kelas' $wm AND MONTH(a.tanggal)='$bulan' AND YEAR(a.tanggal)='$tahun' ORDER BY a.tanggal,s.nama_lengkap");
    while ($a = mysqli_fetch_assoc($ra)) {
        $tgl = date('j', strtotime($a['tanggal']));
        $data_absen[$a['siswa_id']][$tgl] = strtoupper(substr($a['status'],0,1));
    }
}
$jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$title = "Cetak Absensi"; $icon = "fa-print";
require_once '../../includes/header.php';
?>
<?php require_once '../../includes/sidebar_admin.php'; ?>
<?php include '../../includes/topbar_admin.php'; ?>

<div class="main-content"><div class="container-fluid">

<div class="card shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><i class="fas fa-filter text-primary me-2"></i>Filter Cetak Absensi — Admin</div>
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-semibold">Kelas</label>
        <select name="id_kelas" class="form-select" required>
          <option value="">-- Pilih Kelas --</option>
          <?php while($k=mysqli_fetch_assoc($q_kelas)): ?>
          <option value="<?=$k['id']?>" <?=$id_kelas==$k['id']?'selected':''?>><?=e($k['nama_kelas'])?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">Mata Pelajaran</label>
        <select name="id_mapel" class="form-select">
          <option value="">-- Semua Mapel --</option>
          <?php while($m=mysqli_fetch_assoc($q_mapel)): ?>
          <option value="<?=$m['id']?>" <?=$id_mapel==$m['id']?'selected':''?>><?=e($m['nama_mapel'])?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold">Bulan</label>
        <select name="bulan" class="form-select">
          <?php for($i=1;$i<=12;$i++): ?>
          <option value="<?=$i?>" <?=$bulan==$i?'selected':''?>><?=$bulan_nama[$i]?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold">Tahun</label>
        <select name="tahun" class="form-select">
          <?php for($y=date('Y')-1;$y<=date('Y')+1;$y++): ?>
          <option value="<?=$y?>" <?=$tahun==$y?'selected':''?>><?=$y?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Tampilkan</button>
      </div>
    </form>
  </div>
</div>

<?php if($id_kelas && count($data_siswa)>0): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="fw-bold mb-0">Absensi <?=$nama_kelas?> — <?=$nama_mapel?> — <?=$bulan_nama[$bulan]?> <?=$tahun?></h6>
  <button onclick="cetakPDF()" class="btn btn-danger"><i class="fas fa-file-pdf me-1"></i>Cetak PDF</button>
</div>
<div class="card shadow-sm" id="tabelAbsen">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-bordered table-sm mb-0" style="font-size:12px;">
        <thead class="table-dark">
          <tr>
            <th class="text-center" style="width:35px;">No</th>
            <th>Nama Siswa</th>
            <th class="text-center" style="width:55px;">NIS</th>
            <?php for($d=1;$d<=$jumlah_hari;$d++):
              $tgl_full="$tahun-".str_pad($bulan,2,'0',STR_PAD_LEFT)."-".str_pad($d,2,'0',STR_PAD_LEFT);
              $hr=date('N',strtotime($tgl_full));
            ?>
            <th class="text-center <?=$hr==7?'table-warning':''?>" style="width:26px;"><?=$d?></th>
            <?php endfor; ?>
            <th class="text-center bg-success text-white" style="width:30px;">H</th>
            <th class="text-center bg-info text-white" style="width:30px;">I</th>
            <th class="text-center bg-warning" style="width:30px;">S</th>
            <th class="text-center bg-danger text-white" style="width:30px;">A</th>
          </tr>
        </thead>
        <tbody>
          <?php $no=1; foreach($data_siswa as $siswa):
            $id_s=$siswa['id']; $h=$i=$s=$a=0;
            for($d=1;$d<=$jumlah_hari;$d++){
              $st=$data_absen[$id_s][$d]??'';
              if($st=='H')$h++; elseif($st=='I')$i++; elseif($st=='S')$s++; elseif($st=='A')$a++;
            }
          ?>
          <tr>
            <td class="text-center"><?=$no++?></td>
            <td><?=e($siswa['nama_lengkap']??$siswa['nama']??'-')?></td>
            <td class="text-center"><?=e($siswa['nis']??'-')?></td>
            <?php for($d=1;$d<=$jumlah_hari;$d++):
              $st=$data_absen[$id_s][$d]??'';
              $tgl_full="$tahun-".str_pad($bulan,2,'0',STR_PAD_LEFT)."-".str_pad($d,2,'0',STR_PAD_LEFT);
              $hr=date('N',strtotime($tgl_full));
              $warna=match($st){'H'=>'bg-success text-white','I'=>'bg-info text-white','S'=>'bg-warning','A'=>'bg-danger text-white',default=>($hr==7?'bg-warning':'')};
            ?>
            <td class="text-center <?=$warna?>"><?=$st?></td>
            <?php endfor; ?>
            <td class="text-center fw-bold text-success"><?=$h?></td>
            <td class="text-center fw-bold text-info"><?=$i?></td>
            <td class="text-center fw-bold text-warning"><?=$s?></td>
            <td class="text-center fw-bold text-danger"><?=$a?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<div class="mt-2 d-flex gap-3" style="font-size:12px;">
  <span><span class="badge bg-success">H</span> Hadir</span>
  <span><span class="badge bg-info">I</span> Izin</span>
  <span><span class="badge bg-warning text-dark">S</span> Sakit</span>
  <span><span class="badge bg-danger">A</span> Alpa</span>
</div>
<?php elseif($id_kelas): ?>
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Belum ada data absensi untuk periode ini.</div>
<?php endif; ?>

</div></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
function cetakPDF(){
  const{jsPDF}=window.jspdf;
  const doc=new jsPDF({orientation:'landscape',unit:'mm',format:'a4'});
  doc.setFontSize(14);doc.setFont(undefined,'bold');
  doc.text('DAFTAR HADIR SISWA',148,14,{align:'center'});
  doc.setFontSize(11);doc.setFont(undefined,'normal');
  doc.text('PEMERINTAH KOTA PALOPO',148,20,{align:'center'});
  doc.text('DINAS PENDIDIKAN',148,25,{align:'center'});
  doc.setFontSize(12);doc.setFont(undefined,'bold');
  doc.text('SMA NEGERI 4 PALOPO',148,30,{align:'center'});
  doc.setFontSize(10);doc.setFont(undefined,'normal');
  doc.text('Kelas  : <?=$nama_kelas?>',14,40);
  doc.text('Mapel  : <?=$nama_mapel?>',14,46);
  doc.text('Bulan  : <?=$bulan_nama[$bulan]." ".$tahun?>',14,52);
  const tabel=document.querySelector('#tabelAbsen table');
  const headers=[],rows=[];
  tabel.querySelectorAll('thead tr th').forEach(th=>headers.push(th.innerText.trim()));
  tabel.querySelectorAll('tbody tr').forEach(tr=>{const row=[];tr.querySelectorAll('td').forEach(td=>row.push(td.innerText.trim()));rows.push(row);});
  doc.autoTable({
    head:[headers],body:rows,startY:60,
    styles:{fontSize:7,cellPadding:1,halign:'center'},
    columnStyles:{0:{cellWidth:8},1:{cellWidth:38,halign:'left'},2:{cellWidth:14}},
    headStyles:{fillColor:[33,37,41],textColor:255,fontSize:7},
    alternateRowStyles:{fillColor:[245,245,245]},
    didParseCell:function(data){
      const v=data.cell.raw;
      if(v==='H'){data.cell.styles.fillColor=[40,167,69];data.cell.styles.textColor=255;}
      else if(v==='I'){data.cell.styles.fillColor=[23,162,184];data.cell.styles.textColor=255;}
      else if(v==='S'){data.cell.styles.fillColor=[255,193,7];}
      else if(v==='A'){data.cell.styles.fillColor=[220,53,69];data.cell.styles.textColor=255;}
    }
  });
  const fy=doc.lastAutoTable.finalY+10;
  doc.setFontSize(9);
  doc.text('Keterangan: H=Hadir | I=Izin | S=Sakit | A=Alpa',14,fy);
  doc.text('Palopo, <?=date("d")." ".$bulan_nama[$bulan]." ".$tahun?>',230,fy,{align:'right'});
  doc.text('Wali Kelas',230,fy+7,{align:'right'});
  doc.text('(________________________)',230,fy+26,{align:'right'});
  doc.save('Absensi_<?=str_replace(' ','_',$nama_kelas)?>_<?=$bulan_nama[$bulan]?>_<?=$tahun?>.pdf');
}
</script>
<?php require_once '../../includes/footer.php'; ?>