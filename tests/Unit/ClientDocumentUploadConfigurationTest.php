<?php

namespace Tests\Unit;

use Tests\TestCase;

class ClientDocumentUploadConfigurationTest extends TestCase
{
    public function test_livewire_temporary_upload_limit_supports_client_documents(): void
    {
        $maxFileSize = config('client_documents.max_file_size_kb');

        $this->assertSame(25 * 1024, $maxFileSize);
        $this->assertContains('max:'.$maxFileSize, config('livewire.temporary_file_upload.rules'));
    }
}
