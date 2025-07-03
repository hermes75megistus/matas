// public/js/calculator.js - Bölüm 1/3
// Temel yapı, değişkenler ve DOM elementleri

class Calculator {
    constructor() {
        // Ana değişkenler
        this.currentValue = '0';
        this.previousValue = null;
        this.operator = null;
        this.waitingForNewValue = false;
        this.hasDecimalPoint = false;
        this.isResultDisplayed = false;
        
        // Geçmiş işlemler için
        this.history = [];
        this.maxHistoryItems = 50;
        
        // Memory fonksiyonları için
        this.memoryValue = 0;
        
        // DOM elementleri
        this.display = document.getElementById('display');
        this.historyList = document.getElementById('history-list');
        this.memoryIndicator = document.getElementById('memory-indicator');
        
        // Event listeners
        this.initializeEventListeners();
        
        // İlk durumu ayarla
        this.updateDisplay();
        this.loadHistory();
        this.updateMemoryIndicator();
    }

    initializeEventListeners() {
        // Number buttons
        const numberButtons = document.querySelectorAll('.btn-number');
        numberButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.inputNumber(button.textContent);
            });
        });

        // Operator buttons
        const operatorButtons = document.querySelectorAll('.btn-operator');
        operatorButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.inputOperator(button.dataset.operator);
            });
        });

        // Function buttons
        document.getElementById('btn-clear').addEventListener('click', () => this.clear());
        document.getElementById('btn-clear-entry').addEventListener('click', () => this.clearEntry());
        document.getElementById('btn-backspace').addEventListener('click', () => this.backspace());
        document.getElementById('btn-equals').addEventListener('click', () => this.calculate());
        document.getElementById('btn-decimal').addEventListener('click', () => this.inputDecimal());
        document.getElementById('btn-sign').addEventListener('click', () => this.toggleSign());
        
        // Scientific functions
        document.getElementById('btn-sqrt').addEventListener('click', () => this.sqrt());
        document.getElementById('btn-square').addEventListener('click', () => this.square());
        document.getElementById('btn-reciprocal').addEventListener('click', () => this.reciprocal());
        document.getElementById('btn-percent').addEventListener('click', () => this.percent());
        
        // Memory functions
        document.getElementById('btn-mc').addEventListener('click', () => this.memoryClear());
        document.getElementById('btn-mr').addEventListener('click', () => this.memoryRecall());
        document.getElementById('btn-mplus').addEventListener('click', () => this.memoryAdd());
        document.getElementById('btn-mminus').addEventListener('click', () => this.memorySubtract());
        document.getElementById('btn-ms').addEventListener('click', () => this.memoryStore());

        // History functions
        document.getElementById('btn-history-clear').addEventListener('click', () => this.clearHistory());
        
        // Keyboard support
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
        
        // History item click handlers
        this.setupHistoryClickHandlers();
    }

    // BÖLÜM 1 SONU - Sonraki bölümde inputNumber, inputOperator ve temel hesaplama fonksiyonları
// public/js/calculator.js - Bölüm 2/3
// Ana hesaplama fonksiyonları ve input handling

    // Number input handling
    inputNumber(number) {
        if (this.waitingForNewValue || this.isResultDisplayed) {
            this.currentValue = number;
            this.waitingForNewValue = false;
            this.isResultDisplayed = false;
            this.hasDecimalPoint = number.includes('.');
        } else {
            if (this.currentValue === '0' && number !== '.') {
                this.currentValue = number;
            } else {
                this.currentValue += number;
            }
        }
        this.updateDisplay();
    }

    // Operator input handling
    inputOperator(nextOperator) {
        const inputValue = parseFloat(this.currentValue);

        if (this.previousValue === null) {
            this.previousValue = inputValue;
        } else if (this.operator) {
            const currentValue = this.previousValue || 0;
            const newValue = this.performCalculation(currentValue, inputValue, this.operator);

            this.currentValue = String(newValue);
            this.previousValue = newValue;
            this.updateDisplay();
        }

        this.waitingForNewValue = true;
        this.operator = nextOperator;
        this.hasDecimalPoint = false;
        this.isResultDisplayed = false;
    }

    // Main calculation function
    performCalculation(firstValue, secondValue, operator) {
        let result;
        
        switch (operator) {
            case '+':
                result = firstValue + secondValue;
                break;
            case '-':
                result = firstValue - secondValue;
                break;
            case '*':
                result = firstValue * secondValue;
                break;
            case '/':
                if (secondValue === 0) {
                    throw new Error('Division by zero');
                }
                result = firstValue / secondValue;
                break;
            case '%':
                result = firstValue % secondValue;
                break;
            default:
                return secondValue;
        }
        
        // Floating point precision fix
        return Math.round(result * 100000000000) / 100000000000;
    }

    // Calculate and show result
    calculate() {
        const inputValue = parseFloat(this.currentValue);

        if (this.previousValue !== null && this.operator) {
            try {
                const newValue = this.performCalculation(this.previousValue, inputValue, this.operator);
                
                // Add to history
                this.addToHistory(`${this.previousValue} ${this.getOperatorSymbol(this.operator)} ${inputValue} = ${newValue}`);
                
                this.currentValue = String(newValue);
                this.previousValue = null;
                this.operator = null;
                this.waitingForNewValue = true;
                this.hasDecimalPoint = false;
                this.isResultDisplayed = true;
                
                this.updateDisplay();
            } catch (error) {
                this.showError(error.message);
            }
        }
    }

    // Scientific functions
    sqrt() {
        try {
            const value = parseFloat(this.currentValue);
            if (value < 0) {
                throw new Error('Invalid input for square root');
            }
            const result = Math.sqrt(value);
            this.addToHistory(`√${value} = ${result}`);
            this.currentValue = String(result);
            this.isResultDisplayed = true;
            this.updateDisplay();
        } catch (error) {
            this.showError(error.message);
        }
    }

    square() {
        try {
            const value = parseFloat(this.currentValue);
            const result = value * value;
            this.addToHistory(`${value}² = ${result}`);
            this.currentValue = String(result);
            this.isResultDisplayed = true;
            this.updateDisplay();
        } catch (error) {
            this.showError(error.message);
        }
    }

    reciprocal() {
        try {
            const value = parseFloat(this.currentValue);
            if (value === 0) {
                throw new Error('Division by zero');
            }
            const result = 1 / value;
            this.addToHistory(`1/${value} = ${result}`);
            this.currentValue = String(result);
            this.isResultDisplayed = true;
            this.updateDisplay();
        } catch (error) {
            this.showError(error.message);
        }
    }

    percent() {
        try {
            const value = parseFloat(this.currentValue);
            const result = value / 100;
            this.addToHistory(`${value}% = ${result}`);
            this.currentValue = String(result);
            this.isResultDisplayed = true;
            this.updateDisplay();
        } catch (error) {
            this.showError(error.message);
        }
    }

    // Input functions
    inputDecimal() {
        if (this.isResultDisplayed) {
            this.currentValue = '0.';
            this.isResultDisplayed = false;
            this.hasDecimalPoint = true;
        } else if (this.waitingForNewValue) {
            this.currentValue = '0.';
            this.waitingForNewValue = false;
            this.hasDecimalPoint = true;
        } else if (!this.hasDecimalPoint) {
            this.currentValue += '.';
            this.hasDecimalPoint = true;
        }
        this.updateDisplay();
    }

    toggleSign() {
        if (this.currentValue !== '0') {
            this.currentValue = String(-parseFloat(this.currentValue));
            this.updateDisplay();
        }
    }

    // Clear functions
    clear() {
        this.currentValue = '0';
        this.previousValue = null;
        this.operator = null;
        this.waitingForNewValue = false;
        this.hasDecimalPoint = false;
        this.isResultDisplayed = false;
        this.updateDisplay();
    }

    clearEntry() {
        this.currentValue = '0';
        this.hasDecimalPoint = false;
        this.isResultDisplayed = false;
        this.updateDisplay();
    }

    backspace() {
        if (this.isResultDisplayed) {
            this.clear();
            return;
        }

        if (this.currentValue.length > 1) {
            const lastChar = this.currentValue.slice(-1);
            if (lastChar === '.') {
                this.hasDecimalPoint = false;
            }
            this.currentValue = this.currentValue.slice(0, -1);
        } else {
            this.currentValue = '0';
            this.hasDecimalPoint = false;
        }
        this.updateDisplay();
    }

    // BÖLÜM 2 SONU - Sonraki bölümde memory, history, keyboard ve yardımcı fonksiyonlar
// public/js/calculator.js - Bölüm 3/3
// Memory, History, Keyboard ve yardımcı fonksiyonlar

    // Memory functions
    memoryClear() {
        this.memoryValue = 0;
        this.updateMemoryIndicator();
    }

    memoryRecall() {
        this.currentValue = String(this.memoryValue);
        this.isResultDisplayed = true;
        this.updateDisplay();
    }

    memoryAdd() {
        this.memoryValue += parseFloat(this.currentValue);
        this.updateMemoryIndicator();
    }

    memorySubtract() {
        this.memoryValue -= parseFloat(this.currentValue);
        this.updateMemoryIndicator();
    }

    memoryStore() {
        this.memoryValue = parseFloat(this.currentValue);
        this.updateMemoryIndicator();
    }

    // History functions
    addToHistory(entry) {
        this.history.unshift(entry);
        if (this.history.length > this.maxHistoryItems) {
            this.history.pop();
        }
        this.updateHistoryDisplay();
        this.saveHistory();
    }

    clearHistory() {
        this.history = [];
        this.updateHistoryDisplay();
        this.saveHistory();
    }

    // Keyboard support
    handleKeyboard(event) {
        const key = event.key;
        
        // Prevent default for calculator keys
        if ('0123456789+-*/.=EnterBackspaceEscapec%'.includes(key)) {
            event.preventDefault();
        }

        // Numbers
        if ('0123456789'.includes(key)) {
            this.inputNumber(key);
        }
        
        // Operators
        else if (key === '+') {
            this.inputOperator('+');
        }
        else if (key === '-') {
            this.inputOperator('-');
        }
        else if (key === '*') {
            this.inputOperator('*');
        }
        else if (key === '/') {
            this.inputOperator('/');
        }
        else if (key === '%') {
            this.inputOperator('%');
        }
        
        // Special keys
        else if (key === '.' || key === ',') {
            this.inputDecimal();
        }
        else if (key === '=' || key === 'Enter') {
            this.calculate();
        }
        else if (key === 'Backspace') {
            this.backspace();
        }
        else if (key === 'Escape' || key === 'c' || key === 'C') {
            this.clear();
        }
    }

    // Display and UI update functions
    updateDisplay() {
        let displayValue = this.currentValue;
        
        // Format large numbers
        if (Math.abs(parseFloat(displayValue)) >= 1000000000) {
            displayValue = parseFloat(displayValue).toExponential(6);
        } else if (displayValue.length > 12) {
            displayValue = parseFloat(displayValue).toPrecision(10);
        }
        
        this.display.textContent = displayValue;
    }

    updateMemoryIndicator() {
        if (this.memoryValue !== 0) {
            this.memoryIndicator.textContent = 'M';
            this.memoryIndicator.style.visibility = 'visible';
        } else {
            this.memoryIndicator.style.visibility = 'hidden';
        }
    }

    updateHistoryDisplay() {
        if (!this.historyList) return;
        
        this.historyList.innerHTML = '';
        
        this.history.forEach((entry, index) => {
            const historyItem = document.createElement('div');
            historyItem.className = 'history-item';
            historyItem.textContent = entry;
            historyItem.addEventListener('click', () => {
                const result = entry.split(' = ')[1];
                if (result) {
                    this.currentValue = result;
                    this.isResultDisplayed = true;
                    this.updateDisplay();
                }
            });
            this.historyList.appendChild(historyItem);
        });
    }

    setupHistoryClickHandlers() {
        // This will be called after history is updated
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('history-item')) {
                const result = e.target.textContent.split(' = ')[1];
                if (result) {
                    this.currentValue = result;
                    this.isResultDisplayed = true;
                    this.updateDisplay();
                }
            }
        });
    }

    // Error handling
    showError(message) {
        this.display.textContent = 'Error';
        setTimeout(() => {
            this.clear();
        }, 2000);
        console.error('Calculator Error:', message);
    }

    // Utility functions
    getOperatorSymbol(operator) {
        const symbols = {
            '+': '+',
            '-': '-',
            '*': '×',
            '/': '÷',
            '%': '%'
        };
        return symbols[operator] || operator;
    }

    // Storage functions
    saveHistory() {
        try {
            localStorage.setItem('calculatorHistory', JSON.stringify(this.history));
        } catch (error) {
            console.warn('Could not save history to localStorage:', error);
        }
    }

    loadHistory() {
        try {
            const savedHistory = localStorage.getItem('calculatorHistory');
            if (savedHistory) {
                this.history = JSON.parse(savedHistory);
                this.updateHistoryDisplay();
            }
        } catch (error) {
            console.warn('Could not load history from localStorage:', error);
            this.history = [];
        }
    }
}

// Initialize calculator when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.calculator = new Calculator();
});

// Export for module use if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Calculator;
}
