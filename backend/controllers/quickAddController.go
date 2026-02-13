package controllers

import (
	"accountbook/initializers"
	"accountbook/models"
	"database/sql"
	"fmt"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
)

// GetQuickAddTemplates 取得所有快捷新增範本
func GetQuickAddTemplates(c *gin.Context) {
	rows, err := initializers.DB.Query(`
		SELECT q.id, q.name, q.template_type,
			q.account_id, COALESCE(a.name, ''), q.type, q.amount, q.item,
			q.category_id, COALESCE(c.name, ''), q.note,
			q.to_account_id, COALESCE(a2.name, ''), q.sort_order
		FROM quick_add_templates q
		LEFT JOIN accounts a ON q.account_id = a.id
		LEFT JOIN categories c ON q.category_id = c.id
		LEFT JOIN accounts a2 ON q.to_account_id = a2.id
		ORDER BY q.sort_order, q.id
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "查詢快捷範本失敗"})
		return
	}
	defer rows.Close()

	var templates []models.QuickAddTemplateWithNames
	for rows.Next() {
		var t models.QuickAddTemplateWithNames
		var accountID, categoryID, toAccountID sql.NullInt64
		if err := rows.Scan(&t.ID, &t.Name, &t.TemplateType,
			&accountID, &t.AccountName, &t.Type, &t.Amount, &t.Item,
			&categoryID, &t.CategoryName, &t.Note,
			&toAccountID, &t.ToAccountName, &t.SortOrder,
		); err != nil {
			continue
		}
		if accountID.Valid {
			id := int(accountID.Int64)
			t.AccountID = &id
		}
		if categoryID.Valid {
			id := int(categoryID.Int64)
			t.CategoryID = &id
		}
		if toAccountID.Valid {
			id := int(toAccountID.Int64)
			t.ToAccountID = &id
		}
		templates = append(templates, t)
	}

	if templates == nil {
		templates = []models.QuickAddTemplateWithNames{}
	}

	c.JSON(http.StatusOK, templates)
}

// CreateQuickAddTemplate 新增快捷範本
func CreateQuickAddTemplate(c *gin.Context) {
	var input models.QuickAddTemplateInput
	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "輸入格式錯誤，請確認必填欄位"})
		return
	}

	if input.TemplateType != "record" && input.TemplateType != "transfer" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "template_type 必須為 record 或 transfer"})
		return
	}

	if input.Type == "" {
		input.Type = "支出"
	}

	now := time.Now().Format("2006-01-02 15:04:05")

	// 取得最大 sort_order
	var maxOrder int
	initializers.DB.QueryRow("SELECT COALESCE(MAX(sort_order), -1) FROM quick_add_templates").Scan(&maxOrder)

	result, err := initializers.DB.Exec(
		`INSERT INTO quick_add_templates (name, template_type, account_id, type, amount, item, category_id, note, to_account_id, sort_order, created_at, updated_at)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		input.Name, input.TemplateType, input.AccountID, input.Type, input.Amount, input.Item, input.CategoryID, input.Note, input.ToAccountID, maxOrder+1, now, now,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "新增快捷範本失敗"})
		return
	}

	id, _ := result.LastInsertId()
	c.JSON(http.StatusCreated, gin.H{"id": id, "message": "新增成功"})
}

// UpdateQuickAddTemplate 更新快捷範本
func UpdateQuickAddTemplate(c *gin.Context) {
	id := c.Param("id")

	var input models.QuickAddTemplateInput
	if err := c.ShouldBindJSON(&input); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "輸入格式錯誤"})
		return
	}

	if input.TemplateType != "record" && input.TemplateType != "transfer" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "template_type 必須為 record 或 transfer"})
		return
	}

	if input.Type == "" {
		input.Type = "支出"
	}

	now := time.Now().Format("2006-01-02 15:04:05")

	result, err := initializers.DB.Exec(
		`UPDATE quick_add_templates SET name=?, template_type=?, account_id=?, type=?, amount=?, item=?, category_id=?, note=?, to_account_id=?, updated_at=? WHERE id=?`,
		input.Name, input.TemplateType, input.AccountID, input.Type, input.Amount, input.Item, input.CategoryID, input.Note, input.ToAccountID, now, id,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "更新快捷範本失敗"})
		return
	}

	rows, _ := result.RowsAffected()
	if rows == 0 {
		c.JSON(http.StatusNotFound, gin.H{"error": "找不到該範本"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "更新成功"})
}

// DeleteQuickAddTemplate 刪除快捷範本
func DeleteQuickAddTemplate(c *gin.Context) {
	id := c.Param("id")

	result, err := initializers.DB.Exec("DELETE FROM quick_add_templates WHERE id = ?", id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "刪除快捷範本失敗"})
		return
	}

	rows, _ := result.RowsAffected()
	if rows == 0 {
		c.JSON(http.StatusNotFound, gin.H{"error": "找不到該範本"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "刪除成功"})
}

// ExecuteQuickAdd 執行快捷新增（使用範本建立紀錄或轉帳）
func ExecuteQuickAdd(c *gin.Context) {
	id := c.Param("id")

	// 查詢範本
	var t models.QuickAddTemplate
	var accountID, categoryID, toAccountID sql.NullInt64
	err := initializers.DB.QueryRow(`
		SELECT id, name, template_type, account_id, type, amount, item, category_id, note, to_account_id
		FROM quick_add_templates WHERE id = ?
	`, id).Scan(&t.ID, &t.Name, &t.TemplateType, &accountID, &t.Type, &t.Amount, &t.Item, &categoryID, &t.Note, &toAccountID)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": "找不到該快捷範本"})
		return
	}

	if accountID.Valid {
		aid := int(accountID.Int64)
		t.AccountID = &aid
	}
	if categoryID.Valid {
		cid := int(categoryID.Int64)
		t.CategoryID = &cid
	}
	if toAccountID.Valid {
		tid := int(toAccountID.Int64)
		t.ToAccountID = &tid
	}

	today := time.Now().Format("2006-01-02")

	if t.TemplateType == "transfer" {
		executeTransferTemplate(c, t, today)
	} else {
		executeRecordTemplate(c, t, today)
	}
}

func executeRecordTemplate(c *gin.Context, t models.QuickAddTemplate, date string) {
	if t.AccountID == nil || t.CategoryID == nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "範本缺少帳戶或分類設定"})
		return
	}
	if t.Amount <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "範本金額必須大於 0"})
		return
	}
	if t.Item == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "範本缺少項目名稱"})
		return
	}

	tx, err := initializers.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "交易開始失敗"})
		return
	}

	now := time.Now().Format("2006-01-02 15:04:05")

	_, err = tx.Exec(
		"INSERT INTO records (date, account_id, type, amount, item, category_id, note, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
		date, *t.AccountID, t.Type, t.Amount, t.Item, *t.CategoryID, t.Note, now, now,
	)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "新增紀錄失敗"})
		return
	}

	if t.Type == "支出" {
		_, err = tx.Exec("UPDATE accounts SET balance = balance - ?, updated_at = ? WHERE id = ?", t.Amount, now, *t.AccountID)
	} else {
		_, err = tx.Exec("UPDATE accounts SET balance = balance + ?, updated_at = ? WHERE id = ?", t.Amount, now, *t.AccountID)
	}
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "更新帳戶餘額失敗"})
		return
	}

	if err = tx.Commit(); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "交易提交失敗"})
		return
	}

	var accountName, categoryName string
	initializers.DB.QueryRow("SELECT name FROM accounts WHERE id = ?", *t.AccountID).Scan(&accountName)
	initializers.DB.QueryRow("SELECT name FROM categories WHERE id = ?", *t.CategoryID).Scan(&categoryName)

	c.JSON(http.StatusCreated, gin.H{
		"message":       "快捷新增成功",
		"date":          date,
		"account_name":  accountName,
		"type":          t.Type,
		"amount":        t.Amount,
		"item":          t.Item,
		"category_name": categoryName,
		"note":          t.Note,
	})
}

func executeTransferTemplate(c *gin.Context, t models.QuickAddTemplate, date string) {
	if t.AccountID == nil || t.ToAccountID == nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "範本缺少轉出或轉入帳戶設定"})
		return
	}
	if *t.AccountID == *t.ToAccountID {
		c.JSON(http.StatusBadRequest, gin.H{"error": "轉出與轉入帳戶不能相同"})
		return
	}
	if t.Amount <= 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "範本金額必須大於 0"})
		return
	}

	var fromName, toName string
	initializers.DB.QueryRow("SELECT name FROM accounts WHERE id = ?", *t.AccountID).Scan(&fromName)
	initializers.DB.QueryRow("SELECT name FROM accounts WHERE id = ?", *t.ToAccountID).Scan(&toName)

	categoryID := getTransferCategoryID()

	tx, err := initializers.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "交易開始失敗"})
		return
	}

	now := time.Now().Format("2006-01-02 15:04:05")

	_, err = tx.Exec("UPDATE accounts SET balance = balance - ?, updated_at = ? WHERE id = ?",
		t.Amount, now, *t.AccountID)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "轉帳失敗"})
		return
	}

	_, err = tx.Exec("UPDATE accounts SET balance = balance + ?, updated_at = ? WHERE id = ?",
		t.Amount, now, *t.ToAccountID)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "轉帳失敗"})
		return
	}

	outItem := fmt.Sprintf("轉帳至 %s", toName)
	_, err = tx.Exec(
		"INSERT INTO records (date, account_id, type, amount, item, category_id, note, created_at, updated_at) VALUES (?, ?, '支出', ?, ?, ?, ?, ?, ?)",
		date, *t.AccountID, t.Amount, outItem, categoryID, t.Note, now, now,
	)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "建立轉帳紀錄失敗"})
		return
	}

	inItem := fmt.Sprintf("從 %s 轉入", fromName)
	_, err = tx.Exec(
		"INSERT INTO records (date, account_id, type, amount, item, category_id, note, created_at, updated_at) VALUES (?, ?, '收入', ?, ?, ?, ?, ?, ?)",
		date, *t.ToAccountID, t.Amount, inItem, categoryID, t.Note, now, now,
	)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "建立轉帳紀錄失敗"})
		return
	}

	if err = tx.Commit(); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "交易提交失敗"})
		return
	}

	c.JSON(http.StatusCreated, gin.H{
		"message":      "快捷轉帳成功",
		"date":         date,
		"from_account": fromName,
		"to_account":   toName,
		"amount":       t.Amount,
		"note":         t.Note,
	})
}
