<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/NisGeneratorService.php';

/**
 * Unit test NisGeneratorService — memakai SQLite in-memory (BUKAN db produksi).
 * Format NIS: [2 digit tahun][5 digit terakhir NPSN][3 digit urut].
 */
class NisGeneratorServiceTest extends TestCase
{
    /** @var PDO */
    private $pdo;

    private const NPSN = '40500012'; // 5 digit terakhir = '00012'

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            "CREATE TABLE siswa (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama_lengkap TEXT,
                nis TEXT,
                tahun_masuk INTEGER
            )"
        );
    }

    private function service(): NisGeneratorService
    {
        return new NisGeneratorService($this->pdo, self::NPSN);
    }

    private function insert(string $nama, ?string $nis, ?int $tahunMasuk): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO siswa (nama_lengkap, nis, tahun_masuk) VALUES (?,?,?)");
        $stmt->execute([$nama, $nis, $tahunMasuk]);
    }

    public function testGenerateFormatTahunNpsn(): void
    {
        $nis = $this->service()->generateSementara(2026);
        // prefix '26' + '00012' + urut '001'
        $this->assertSame('2600012001', $nis);
        $this->assertSame(10, strlen($nis));
    }

    public function testGenerateUrutBertambah(): void
    {
        $this->insert('Siswa 1', '2600012001', 2026);
        $this->assertSame('2600012002', $this->service()->generateSementara(2026));
    }

    public function testFinalisasiUrutAbjad(): void
    {
        $this->insert('Zaini', '2600012003', 2026);
        $this->insert('Ahmad', '2600012001', 2026);
        $this->insert('Budi',   '2600012002', 2026);

        $rencana = $this->service()->finalisasiUrutAbjad(2026);

        $this->assertCount(3, $rencana);
        $this->assertSame(['Ahmad', 'Budi', 'Zaini'], array_column($rencana, 'nama_lengkap'));
        $this->assertSame('2600012001', $rencana[0]['nis_baru']);
        $this->assertSame('2600012002', $rencana[1]['nis_baru']);
        $this->assertSame('2600012003', $rencana[2]['nis_baru']);
        // NIS lama dipertahankan dari record asli masing-masing siswa
        $this->assertSame('2600012001', $rencana[0]['nis_lama']); // Ahmad
        $this->assertSame('2600012002', $rencana[1]['nis_lama']); // Budi
        $this->assertSame('2600012003', $rencana[2]['nis_lama']); // Zaini
    }

    public function testGenerateMelebihi999MelemparException(): void
    {
        // Isi 999 NIS dengan prefix yang sama, supaya urut berikutnya = 1000.
        for ($i = 1; $i <= 999; $i++) {
            $this->insert('Siswa ' . $i, '2600012' . str_pad((string) $i, 3, '0', STR_PAD_LEFT), 2026);
        }

        $this->expectException(\RuntimeException::class);
        $this->service()->generateSementara(2026);
    }

    public function testNpsnKurang5DigitMelemparException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NisGeneratorService($this->pdo, '123');
    }
}