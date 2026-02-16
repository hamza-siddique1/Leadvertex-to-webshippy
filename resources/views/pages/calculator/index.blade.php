<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Running Total Calculator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .calculator-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 30px;
            max-width: 400px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            color: #999;
            font-size: 14px;
        }

        .running-display {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            min-height: 60px;
            max-height: 120px;
            overflow-y: auto;
        }

        .running-display .expression {
            color: #666;
            font-size: 18px;
            word-wrap: break-word;
            line-height: 1.5;
        }

        .display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: right;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            word-wrap: break-word;
            overflow-x: auto;
        }

        .buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        button {
            padding: 20px;
            font-size: 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        button:active {
            transform: translateY(0);
        }

        .btn-number, .btn-decimal {
            background: #f8f9fa;
            color: #333;
        }

        .btn-number:hover, .btn-decimal:hover {
            background: #e9ecef;
        }

        .btn-operator {
            background: #667eea;
            color: white;
        }

        .btn-operator:hover {
            background: #5568d3;
        }

        .btn-equals {
            background: #28a745;
            color: white;
            grid-column: span 2;
        }

        .btn-equals:hover {
            background: #218838;
        }

        .btn-clear {
            background: #dc3545;
            color: white;
        }

        .btn-clear:hover {
            background: #c82333;
        }

        .btn-backspace {
            background: #ffc107;
            color: white;
        }

        .btn-backspace:hover {
            background: #e0a800;
        }

        .history-section {
            margin-top: 20px;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .history-header h3 {
            color: #667eea;
            font-size: 18px;
        }

        .clear-history-btn {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .clear-history-btn:hover {
            background: #c82333;
        }

        .history-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            max-height: 250px;
            overflow-y: auto;
        }

        .history-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .history-item:last-child {
            margin-bottom: 0;
        }

        .history-expression {
            color: #666;
            font-size: 14px;
            flex: 1;
        }

        .history-result {
            color: #667eea;
            font-weight: bold;
            font-size: 16px;
            margin-left: 10px;
        }

        .no-history {
            text-align: center;
            color: #999;
            padding: 20px;
            font-size: 14px;
        }

        /* Scrollbar styling */
        .history-list::-webkit-scrollbar,
        .running-display::-webkit-scrollbar {
            width: 6px;
        }

        .history-list::-webkit-scrollbar-track,
        .running-display::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .history-list::-webkit-scrollbar-thumb,
        .running-display::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .history-list::-webkit-scrollbar-thumb:hover,
        .running-display::-webkit-scrollbar-thumb:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="calculator-container">
        <div class="header">
            <h1>Running Total Calculator</h1>
            <p>All operations are saved automatically</p>
        </div>

        <div class="running-display">
            <div class="expression" id="runningExpression">Start typing...</div>
        </div>

        <div class="display" id="display">0</div>

        <div class="buttons">
            <button class="btn-clear" onclick="clearAll()">C</button>
            <button class="btn-backspace" onclick="backspace()">⌫</button>
            <button class="btn-operator" onclick="appendOperator('/')">/</button>
            <button class="btn-operator" onclick="appendOperator('*')">×</button>

            <button class="btn-number" onclick="appendNumber('7')">7</button>
            <button class="btn-number" onclick="appendNumber('8')">8</button>
            <button class="btn-number" onclick="appendNumber('9')">9</button>
            <button class="btn-operator" onclick="appendOperator('-')">-</button>

            <button class="btn-number" onclick="appendNumber('4')">4</button>
            <button class="btn-number" onclick="appendNumber('5')">5</button>
            <button class="btn-number" onclick="appendNumber('6')">6</button>
            <button class="btn-operator" onclick="appendOperator('+')">+</button>

            <button class="btn-number" onclick="appendNumber('1')">1</button>
            <button class="btn-number" onclick="appendNumber('2')">2</button>
            <button class="btn-number" onclick="appendNumber('3')">3</button>
            <button class="btn-equals" onclick="calculate()">=</button>

            <button class="btn-number" onclick="appendNumber('0')" style="grid-column: span 2;">0</button>
            <button class="btn-decimal" onclick="appendNumber('.')">.</button>
        </div>

        <div class="history-section">
            <div class="history-header">
                <h3>History</h3>
                <button class="clear-history-btn" onclick="clearHistory()">Clear All</button>
            </div>
            <div class="history-list" id="historyList">
                <div class="no-history">No calculations yet</div>
            </div>
        </div>
    </div>

    <script>
        let currentExpression = '';
        let currentDisplay = '0';
        let lastResult = null;

        // Load history from localStorage on page load
        window.onload = function() {
            loadHistory();
        };

        function appendNumber(num) {
            if (currentDisplay === '0' && num !== '.') {
                currentDisplay = num;
            } else {
                currentDisplay += num;
            }

            if (currentExpression === '' && lastResult !== null) {
                // Starting fresh after a calculation
                currentExpression = num;
            } else {
                currentExpression += num;
            }

            updateDisplay();
        }

        function appendOperator(operator) {
            // Don't allow operator at the start
            if (currentExpression === '') {
                if (lastResult !== null) {
                    currentExpression = lastResult + ' ' + operator + ' ';
                    currentDisplay = '0';
                }
                updateDisplay();
                return;
            }

            // Replace last operator if user changes their mind
            const lastChar = currentExpression.trim().slice(-1);
            if (['+', '-', '*', '/'].includes(lastChar)) {
                currentExpression = currentExpression.trim().slice(0, -1) + operator + ' ';
            } else {
                currentExpression += ' ' + operator + ' ';
            }

            currentDisplay = '0';
            updateDisplay();
        }

        function calculate() {
            if (currentExpression === '') return;

            try {
                // Replace × with * for evaluation
                const expression = currentExpression.replace(/×/g, '*');
                const result = eval(expression);

                // Save to history
                saveToHistory(currentExpression, result);

                // Update display
                currentDisplay = result.toString();
                lastResult = result.toString();
                currentExpression = '';
                updateDisplay();

                // Load and display updated history
                loadHistory();
            } catch (error) {
                currentDisplay = 'Error';
                updateDisplay();
            }
        }

        function clearAll() {
            currentExpression = '';
            currentDisplay = '0';
            lastResult = null;
            updateDisplay();
        }

        function backspace() {
            if (currentDisplay.length > 1) {
                currentDisplay = currentDisplay.slice(0, -1);
                currentExpression = currentExpression.slice(0, -1);
            } else {
                currentDisplay = '0';
                currentExpression = currentExpression.slice(0, -1);
            }
            updateDisplay();
        }

        function updateDisplay() {
            document.getElementById('display').textContent = currentDisplay;
            document.getElementById('runningExpression').textContent =
                currentExpression || 'Start typing...';
        }

        function saveToHistory(expression, result) {
            let history = JSON.parse(localStorage.getItem('calculatorHistory') || '[]');

            const historyItem = {
                expression: expression,
                result: result,
                timestamp: new Date().toLocaleString()
            };

            history.unshift(historyItem); // Add to beginning

            // Keep only last 50 calculations
            if (history.length > 50) {
                history = history.slice(0, 50);
            }

            localStorage.setItem('calculatorHistory', JSON.stringify(history));
        }

        function loadHistory() {
            const history = JSON.parse(localStorage.getItem('calculatorHistory') || '[]');
            const historyList = document.getElementById('historyList');

            if (history.length === 0) {
                historyList.innerHTML = '<div class="no-history">No calculations yet</div>';
                return;
            }

            historyList.innerHTML = history.map(item => `
                <div class="history-item">
                    <div class="history-expression">${item.expression} =</div>
                    <div class="history-result">${item.result}</div>
                </div>
            `).join('');
        }

        function clearHistory() {
            if (confirm('Are you sure you want to clear all history?')) {
                localStorage.removeItem('calculatorHistory');
                loadHistory();
            }
        }

        // Keyboard support
        document.addEventListener('keydown', function(event) {
            const key = event.key;

            if (key >= '0' && key <= '9') {
                appendNumber(key);
            } else if (key === '.') {
                appendNumber('.');
            } else if (key === '+' || key === '-' || key === '*' || key === '/') {
                appendOperator(key);
            } else if (key === 'Enter' || key === '=') {
                event.preventDefault();
                calculate();
            } else if (key === 'Backspace') {
                event.preventDefault();
                backspace();
            } else if (key === 'Escape') {
                clearAll();
            }
        });
    </script>
</body>
</html>
