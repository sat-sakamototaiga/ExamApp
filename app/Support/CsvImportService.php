<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvImportService
{
    /**
     * @return array{stream: resource, header: array<int, string>}
     */
    public function createReaderFromUpload(UploadedFile $file): array
    {
        $filePath = $file->getRealPath();

        if ($filePath === false) {
            throw new RuntimeException('CSVファイルの読み込みに失敗しました。');
        }

        $csvData = file_get_contents($filePath);
        if ($csvData === false) {
            throw new RuntimeException('CSVファイルの読み込みに失敗しました。');
        }

        // 取り込み側はUTF-8前提のため、Shift_JIS系で来たCSVもここで正規化する。
        if (! mb_check_encoding($csvData, 'UTF-8')) {
            $csvData = mb_convert_encoding($csvData, 'UTF-8', 'SJIS-win,CP932,UTF-8');
        }

        // ヘッダー一致判定を安定させるため、UTF-8 BOMを除去する。
        if (str_starts_with($csvData, "\xEF\xBB\xBF")) {
            $csvData = substr($csvData, 3);
        }

        $csvStream = fopen('php://temp', 'r+');
        if ($csvStream === false) {
            throw new RuntimeException('CSV処理の初期化に失敗しました。');
        }

        fwrite($csvStream, $csvData);
        rewind($csvStream);

        $header = fgetcsv($csvStream);
        if ($header === false) {
            fclose($csvStream);
            throw new RuntimeException('CSVヘッダーの読み込みに失敗しました。');
        }

        return [
            'stream' => $csvStream,
            'header' => array_map(static fn ($value) => trim((string) $value), $header),
        ];
    }

    /**
     * @param array<int, string> $header
     * @param array<int, array<int, string>> $sampleRows
     */
    public function streamTemplateDownload(string $filename, array $header, array $sampleRows = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $sampleRows) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            // Excel互換性のため、テンプレートはBOM付きUTF-8で配信する。
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $header);

            foreach ($sampleRows as $sampleRow) {
                fputcsv($output, $sampleRow);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}