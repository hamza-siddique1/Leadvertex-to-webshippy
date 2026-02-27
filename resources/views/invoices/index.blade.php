<!DOCTYPE html>
<html>
<head>
    <title>File Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 30px; color: #333; }

        /* Breadcrumb */
        .breadcrumb { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 4px; }
        .breadcrumb a { color: #4CAF50; text-decoration: none; margin-right: 5px; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { color: #666; margin: 0 5px; }

        .search-box { margin-bottom: 20px; }
        .search-box input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }

        /* Folder cards */
        .folders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .folder-card { background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; text-decoration: none; color: #333; display: block; }
        .folder-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .folder-icon { font-size: 48px; margin-bottom: 10px; }
        .folder-name { font-weight: bold; margin-bottom: 5px; }
        .folder-info { font-size: 12px; color: #666; }

        /* Files table */
        .files-section { margin-top: 30px; }
        .section-title { font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #4CAF50; color: white; padding: 12px; text-align: left; font-weight: 600; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f9f9f9; }
        .download-btn { background: #4CAF50; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-size: 14px; display: inline-block; }
        .download-btn:hover { background: #45a049; }
        .badge { background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 3px; font-size: 12px; }
        .total { margin-bottom: 15px; color: #666; font-size: 14px; }
        .empty-state { text-align: center; padding: 40px; color: #999; }

        .folder-card-wrapper {
            position: relative;
        }

        .folder-card {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            display: block;
            margin-bottom: 10px;
        }

        .folder-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .download-folder-btn {
            display: block;
            background: #4CAF50;
            color: white;
            padding: 8px 12px;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.3s;
        }

        .download-folder-btn:hover {
            background: #45a049;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📁 File Manager</h1>

        <!-- Breadcrumb Navigation -->
        <div class="breadcrumb">
            <a href="{{ route('files.index') }}">🏠 Home</a>
            @if($folder)
                @php
                    $parts = explode('/', $folder);
                    $currentPath = '';
                @endphp
                @foreach($parts as $part)
                    @php
                        $currentPath .= ($currentPath ? '/' : '') . $part;
                    @endphp
                    <span>/</span>
                    <a href="{{ route('files.index', ['folder' => $currentPath]) }}">{{ $part }}</a>
                @endforeach
            @endif
        </div>

        <!-- Add this after the breadcrumb and before the total count -->
        @if($folder && count($files) > 0)
        <div style="margin-bottom: 20px;">
            <a href="{{ route('files.download.folder', ['folder' => $folder]) }}"
            class="download-btn"
            onclick="return confirm('Download all {{ count($files) }} files in this folder as ZIP?')">
                📦 Download All Files as ZIP ({{ count($files) }} files)
            </a>

            <a href="{{ route('files.print.folder', ['folder' => $folder]) }}"
            class="download-btn"
            onclick="window.open(this.href, '_blank', 'width=900,height=700'); return false;">
                🖨️ Print All PDFs ({{ count($files) }} files)
            </a>
        </div>
        @endif

        <div class="total">
            <strong>📂 Folders:</strong> {{ count($folders) }} |
            <strong>📄 Files:</strong> {{ count($files) }}
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Search files and folders..." onkeyup="searchFiles()">
        </div>

        <!-- Folders Grid -->
        @if(count($folders) > 0)
            <div class="folders-grid" id="foldersGrid">
                @foreach($folders as $folderItem)
                <div class="folder-card-wrapper">
                    <a href="{{ route('files.index', ['folder' => $folderItem['path']]) }}" class="folder-card">
                        <div class="folder-icon">📁</div>
                        <div class="folder-name">{{ $folderItem['name'] }}</div>
                        <div class="folder-info">
                            {{ $folderItem['file_count'] }} file(s)<br>
                            {{ $folderItem['modified'] }}
                        </div>
                    </a>
                    @if($folderItem['file_count'] > 0)
                    <a href="{{ route('files.download.folder', ['folder' => $folderItem['path']]) }}"
                    class="download-folder-btn"
                    onclick="return confirm('Download all {{ $folderItem['file_count'] }} files in this folder as ZIP?')">
                        📦 Download ZIP ({{ $folderItem['file_count'] }} files)
                    </a>
                    @endif
                </div>
                @endforeach
            </div>
        @endif

        <!-- Files Table -->
        @if(count($files) > 0)
            <div class="files-section">
                <div class="section-title">📄 Files in this folder</div>
                <table id="filesTable">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $file)
                        <tr>
                            <td>{{ $file['name'] }}</td>
                            <td>{{ $file['size'] }}</td>
                            <td>{{ $file['modified'] }}</td>
                            <td>
                                <a href="{{ route('files.download', ['folder' => $file['path'], 'file' => $file['name']]) }}" class="download-btn">
                                    ⬇️ Download
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(count($folders) === 0 && count($files) === 0)
            <div class="empty-state">
                📭 This folder is empty
            </div>
        @endif
    </div>

    <script>
        function searchFiles() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();

            // Search folders
            const foldersGrid = document.getElementById('foldersGrid');
            if (foldersGrid) {
                const folders = foldersGrid.getElementsByClassName('folder-card');
                for (let i = 0; i < folders.length; i++) {
                    const txtValue = folders[i].textContent || folders[i].innerText;
                    folders[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
                }
            }

            // Search files
            const table = document.getElementById('filesTable');
            if (table) {
                const tr = table.getElementsByTagName('tr');
                for (let i = 1; i < tr.length; i++) {
                    const td = tr[i].getElementsByTagName('td');
                    let found = false;

                    for (let j = 0; j < td.length - 1; j++) {
                        if (td[j]) {
                            const txtValue = td[j].textContent || td[j].innerText;
                            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                found = true;
                                break;
                            }
                        }
                    }

                    tr[i].style.display = found ? '' : 'none';
                }
            }
        }
    </script>
</body>
</html>
