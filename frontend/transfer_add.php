<?php
$pageTitle = '新增轉帳';
$currentPage = 'add';
include __DIR__ . '/components/header.php';
?>

<div class="page-toggle">
    <a href="/record_add.php" class="toggle-item">記帳</a>
    <a href="/transfer_add.php" class="toggle-item active">轉帳</a>
</div>

<div id="quickadd-bar" style="display:none;">
    <div style="padding:8px 16px 0;font-size:12px;color:#999;">快捷新增</div>
    <div id="quickadd-buttons" class="quickadd-bar-buttons"></div>
</div>

<form id="transfer-form">
    <div class="form-group">
        <label>日期</label>
        <input type="date" id="transfer-date" required>
        <div class="date-shortcuts">
            <button type="button" data-offset="-1">昨天</button>
            <button type="button" data-offset="0" class="active">今天</button>
            <button type="button" data-offset="1">明天</button>
        </div>
    </div>
    <div class="form-group">
        <label>轉出帳戶</label>
        <select id="transfer-from"></select>
    </div>
    <div class="form-group">
        <label>轉入帳戶</label>
        <select id="transfer-to"></select>
    </div>
    <div class="form-group">
        <label>金額</label>
        <input type="number" id="transfer-amount" step="1" min="0" required placeholder="請輸入金額">
    </div>
    <div class="form-group">
        <label>備註</label>
        <textarea id="transfer-note" placeholder="選填"></textarea>
    </div>
</form>

<button class="btn btn-primary" id="btn-transfer">確認轉帳</button>

<script>
    // 設定預設日期為今天
    function setDateByOffset(offset) {
        const d = new Date();
        d.setDate(d.getDate() + offset);
        const dateStr = d.toISOString().split('T')[0];
        document.getElementById('transfer-date').value = dateStr;

        document.querySelectorAll('.date-shortcuts button').forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.dataset.offset) === offset);
        });
    }

    setDateByOffset(0);

    // 日期快捷按鈕
    document.querySelectorAll('.date-shortcuts button').forEach(btn => {
        btn.addEventListener('click', () => {
            setDateByOffset(parseInt(btn.dataset.offset));
        });
    });

    // 載入帳戶選單
    async function init() {
        try {
            const accounts = await API.getAccounts();

            const fromSelect = document.getElementById('transfer-from');
            const toSelect = document.getElementById('transfer-to');

            (accounts || []).forEach(a => {
                const opt1 = document.createElement('option');
                opt1.value = a.id;
                opt1.textContent = a.name;
                fromSelect.appendChild(opt1);

                const opt2 = document.createElement('option');
                opt2.value = a.id;
                opt2.textContent = a.name;
                toSelect.appendChild(opt2);
            });

            // 預設轉入帳戶為第二個
            if (accounts && accounts.length > 1) {
                toSelect.value = accounts[1].id;
            }
        } catch (e) {
            showToast('載入資料失敗');
        }
    }

    // 確認轉帳
    document.getElementById('btn-transfer').addEventListener('click', async () => {
        const data = {
            date: document.getElementById('transfer-date').value,
            from_account_id: parseInt(document.getElementById('transfer-from').value),
            to_account_id: parseInt(document.getElementById('transfer-to').value),
            amount: parseFloat(document.getElementById('transfer-amount').value),
            note: document.getElementById('transfer-note').value,
        };

        if (!data.date || !data.amount) {
            showToast('請填寫必要欄位');
            return;
        }

        if (data.from_account_id === data.to_account_id) {
            showToast('轉出與轉入帳戶不能相同');
            return;
        }

        try {
            await API.createTransfer(data);
            showToast('轉帳成功');
            setTimeout(() => window.location.href = '/', 500);
        } catch (e) {
            showToast(e.message || '轉帳失敗');
        }
    });

    // 載入快捷新增按鈕
    async function loadQuickAddButtons() {
        try {
            const templates = await API.getQuickAddTemplates();
            if (!templates || templates.length === 0) return;

            const bar = document.getElementById('quickadd-bar');
            const container = document.getElementById('quickadd-buttons');
            bar.style.display = 'block';

            container.innerHTML = templates.map(t =>
                `<button type="button" class="quickadd-btn" data-id="${t.id}">${t.name}</button>`
            ).join('');

            container.querySelectorAll('.quickadd-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (btn.disabled) return;
                    btn.disabled = true;
                    btn.textContent = '...';
                    try {
                        const result = await API.executeQuickAdd(btn.dataset.id);
                        showToast(result.message || '新增成功');
                        setTimeout(() => window.location.href = '/', 500);
                    } catch (e) {
                        showToast(e.message || '快捷新增失敗');
                        location.reload();
                    }
                });
            });
        } catch (e) {
            // 靜默失敗
        }
    }

    init();
    loadQuickAddButtons();
</script>

<style>
.quickadd-bar-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px 16px;
}
.quickadd-btn {
    padding: 6px 14px;
    border: 1px solid #4A90D9;
    border-radius: 16px;
    background: #fff;
    color: #4A90D9;
    font-size: 13px;
    cursor: pointer;
    white-space: nowrap;
}
.quickadd-btn:active {
    background: #4A90D9;
    color: #fff;
}
.quickadd-btn:disabled {
    opacity: 0.5;
    cursor: default;
}
</style>

<?php include __DIR__ . '/components/footer.php'; ?>
