<?php

namespace App\Core\Services\FileHandle\Contracts;

use App\Core\Services\FileHandle\Dtos\FileClassifyDto;
use App\Core\Services\FileHandle\Dtos\MatchResultDto;
use Illuminate\Http\UploadedFile;

interface FileHandleContract
{
    //檔案分類
    public function classify(array $files_in_db,array $files):FileClassifyDto;
    //檔案預存
    public function cache(UploadedFile $file):string;
    //檔案儲存處理
    public function store(array|string $file_key):array;
    //檔案檢查處理
    public function match(array $files_in_db,array $files_not_modify):MatchResultDto;
    //檔案刪除處理
    public function delete(array $files):void;

}