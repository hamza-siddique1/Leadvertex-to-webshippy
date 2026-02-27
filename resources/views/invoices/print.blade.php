<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Invoices</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .print-header {
            margin-bottom: 30px;
        }
        .print-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .folder-name {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .file-count {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 10px 20px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 30px;
        }
        .print-controls {
            margin-bottom: 30px;
        }
        .btn {
            padding: 12px 30px;
            margin: 0 10px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-print {
            background-color: #4caf50;
            color: white;
        }
        .btn-print:hover {
            background-color: #45a049;
        }
        .btn-close {
            background-color: #f44336;
            color: white;
        }
        .btn-close:hover {
            background-color: #da190b;
        }
        .status-message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            display: none;
        }
        .status-message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            display: block;
        }
        .status-message.loading {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            display: block;
        }
        .pdf-list {
            text-align: left;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .pdf-list h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .pdf-item {
            padding: 10px;
            margin: 5px 0;
            background-color: #f9f9f9;
            border-left: 4px solid #4caf50;
            color: #666;
        }
        @media print {
            body {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="print-header">
            <h1>🖨️ Print Invoices</h1>
            @if($folder)
                <div class="folder-name">Folder: <strong>{{ $folder }}</strong></div>
            @endif
            <div class="file-count">{{ count($pdfFiles) }} PDF(s) ready to print</div>
        </div>

        <div class="status-message info" id="infoMessage">
            ℹ️ All PDFs will be opened in new tabs. You can print them all at once using your browser's print functionality.
        </div>

        <div class="status-message loading" id="loadingMessage" style="display: none;">
            ⏳ Opening PDFs... Please wait.
        </div>

        <div class="print-controls">
            <button class="btn btn-print" onclick="openAllAndPrint()">
                🖨️ Open All & Print
            </button>
            <button class="btn btn-close" onclick="window.close()">
                ❌ Close
            </button>
        </div>

        <div class="pdf-list">
            <h3>Files to be printed:</h3>
            @foreach($pdfFiles as $pdf)
                <div class="pdf-item">📄 {{ $pdf['name'] }}</div>
            @endforeach
        </div>
    </div>

    <script>
        // PDFs to open (passed from Laravel)
        const pdfFiles = @json($pdfFiles);

        function openAllAndPrint() {
            const loadingMsg = document.getElementById('loadingMessage');
            const infoMsg = document.getElementById('infoMessage');
            const printBtn = document.querySelector('.btn-print');

            // Show loading message
            infoMsg.style.display = 'none';
            loadingMsg.style.display = 'block';
            printBtn.disabled = true;

            // Open each PDF in a new tab with a slight delay between them
            pdfFiles.forEach((pdf, index) => {
                setTimeout(() => {
                    window.open(pdf.url, '_blank');
                }, index * 500); // 500ms delay between opening each PDF
            });

            // After all PDFs are opened, show completion message
            setTimeout(() => {
                loadingMsg.style.display = 'none';
                const completionMsg = document.createElement('div');
                completionMsg.className = 'status-message info';
                completionMsg.textContent = '✅ All PDFs opened! Use Ctrl+P (or Cmd+P) to print from each tab.';
                document.querySelector('.print-controls').appendChild(completionMsg);
                printBtn.disabled = false;
            }, pdfFiles.length * 500);
        }

        // Optional: Auto-trigger on page load
        // Uncomment the line below if you want PDFs to open automatically
        //window.addEventListener('load', () => {
        //     setTimeout(openAllAndPrint, 500);
         //});
    </script>
</body>
</html>
