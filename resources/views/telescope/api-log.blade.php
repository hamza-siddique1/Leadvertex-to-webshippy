
<!-- Results Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('CRM') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($api_logs as $entry)
                                <tr>
                                    <td>#{{ $entry->id }}</td>
                                    <td>{{ $entry->crm }}</td>
                                    <td><span class="badge bg-primary">{{ $entry->request_method }}</span></td>
                                    <td><span class="badge bg-{{ $entry->status === '200' ? 'success' : 'danger' }}">{{ $entry->status }}</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="toggleDetails({{ $entry->id }})">
                                            Show Details
                                        </button>
                                    </td>
                                </tr>
                                <tr class="details-row" id="details-{{ $entry->id }}" style="display: none;">
                                    <td colspan="5">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Request Payload:</strong>
                                                <pre><code>{{ json_encode(json_decode($entry->request_body), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Response:</strong>
                                                <pre><code>{{ json_encode(json_decode($entry->response_body), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ __('No results found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

<script>
function toggleDetails(id) {
    const row = document.getElementById(`details-${id}`);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

<style>
.details-row pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    max-height: 400px;
    overflow-y: auto;
}
</style>
                </div>
            </div>
        </div>
    </div>

