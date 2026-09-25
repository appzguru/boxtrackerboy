<?php

namespace App\Controllers;

use App\Models\BoxModel;

class Csv extends BaseController
{
    public function importForm()
    {
        return $this->view('import', ['title' => 'Labels importeren — Boxtracker']);
    }

    public function import()
    {
        $file = $this->request->getFile('csv');
        if (! $file || ! $file->isValid()) {
            return $this->view('import', ['title' => 'Labels importeren — Boxtracker', 'error' => 'Geen geldig bestand gekozen.']);
        }

        $content = file_get_contents($file->getTempName());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $boxes   = new BoxModel();
        $added   = 0;
        $skipped = 0;

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        foreach ($lines as $i => $line) {
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line, ';');
            if ($i === 0 && ! ctype_digit(trim($cols[0] ?? ''))) {
                continue; // header
            }
            $nummer = (int) trim($cols[0] ?? '');
            $code   = trim($cols[1] ?? '');
            if ($nummer <= 0 || ! preg_match('/^[a-zA-Z0-9]{4,8}$/', $code)) {
                continue;
            }

            // Nummer bestaat al in deze verhuizing, of het token is ergens al in gebruik
            // (tokens zijn globaal uniek): overslaan, nooit overschrijven.
            if ($boxes->findByNummer($nummer) || BoxModel::locateToken($code)) {
                $skipped++;
                continue;
            }

            $boxes->insert(['nummer' => $nummer, 'token' => $code, 'status' => 'leeg']);
            $added++;
        }

        return $this->view('import', [
            'title'   => 'Labels importeren — Boxtracker',
            'done'    => true,
            'added'   => $added,
            'skipped' => $skipped,
        ]);
    }

    public function export()
    {
        $rows = (new BoxModel())->orderBy('nummer', 'ASC')->findAll();

        $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="boxtracker-export-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['nummer', 'token', 'status', 'omschrijving', 'eigenaar', 'einddoel', 'huidige_locatie', 'fragiel', 'eerst_openen', 'ingepakt_door', 'ingepakt_op'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                box_nr($r['nummer']), $r['token'], $r['status'], $r['omschrijving'], $r['eigenaar'],
                $r['einddoel'], $r['huidige_locatie'], $r['fragiel'] ? '1' : '0', $r['eerst_openen'] ? '1' : '0',
                $r['ingepakt_door'], $r['ingepakt_op'],
            ], ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response->setBody($csv);
    }
}
