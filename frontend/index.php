<?php
$pageTitle = '記帳本';
$currentPage = 'home';
$extraScripts = ['/assets/js/calendar.js'];
include __DIR__ . '/components/header.php';
?>

<style>
.loading-message {
    text-align: center;
    padding: 32px 16px;
    color: #999;
    font-size: 13px;
    list-style: none;
}
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #e0e0e0;
    border-top-color: #4A90D9;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    vertical-align: middle;
    margin-right: 6px;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<div id="calendar-container"></div>

<div id="day-summary" class="day-summary" style="display:none;">
    <span id="summary-date"></span>
    <span>
        收入:<span id="summary-income" style="color:#27ae60;">0</span>
        支出:<span id="summary-expense" style="color:#e74c3c;">0</span>
    </span>
</div>

<ul id="record-list" class="record-list">
    <li class="empty-message">選擇日期查看紀錄</li>
</ul>

<script>
    // 初始化行事曆，選擇日期時載入當日紀錄
    const calendar = new Calendar(
        document.getElementById('calendar-container'),
        loadRecords
    );

    // 初始載入月份與當天資料
    calendar.loadMonthData();
    loadRecords(calendar.selectedDate);

    // 載入指定日期的紀錄列表
    async function loadRecords(date) {
        const listEl = document.getElementById('record-list');
        const summaryEl = document.getElementById('day-summary');

        // 顯示 Loading 動畫
        listEl.innerHTML = '<li class="loading-message"><span class="loading-spinner"></span>載入中...</li>';

        try {
            const data = await API.getRecordsByDate(date);

            // 更新日期摘要
            document.getElementById('summary-date').textContent =
                date.substring(5).replace('-', '/');
            document.getElementById('summary-income').textContent =
                Number(data.total_income).toLocaleString();
            document.getElementById('summary-expense').textContent =
                Number(data.total_expense).toLocaleString();
            summaryEl.style.display = 'flex';

            // 渲染紀錄列表
            if (!data.records || data.records.length === 0) {
                listEl.innerHTML = '<li class="empty-message">今天還沒有紀錄</li>';
                return;
            }

            listEl.innerHTML = data.records.map(r => `
                <a href="/record_edit.php?id=${r.id}" class="record-item">
                    <div class="record-info">
                        <div class="record-name">${escapeHtml(r.item)}</div>
                        <div class="record-category">${escapeHtml(r.category_name)} · ${escapeHtml(r.account_name)}</div>
                    </div>
                    <div class="record-amount ${r.type === '支出' ? 'expense' : 'income'}">
                        ${formatAmount(r.amount, r.type)}
                    </div>
                </a>
            `).join('');
        } catch (e) {
            listEl.innerHTML = '<li class="empty-message">載入失敗</li>';
        }
    }

    // HTML 跳脫防止 XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
