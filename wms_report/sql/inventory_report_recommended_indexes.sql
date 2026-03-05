-- Recommended indexes for inventory report performance
-- Run one-by-one in your DB and skip any index that already exists.

CREATE INDEX idx_wms_itemlist_recipient_itemcode ON wms_itemlist (recipient, item_code);
CREATE INDEX idx_wms_itemlist_itemcode ON wms_itemlist (item_code);

CREATE INDEX idx_wms_inventory_records_item_month_year ON wms_inventory_records (item_code, month, year);

CREATE INDEX idx_wms_inventory_pcount_item_transdate ON wms_inventory_pcount (item_code, trans_date);
CREATE INDEX idx_wms_inventory_pcount_transdate_item ON wms_inventory_pcount (trans_date, item_code);

CREATE INDEX idx_wms_receiving_details_item_received_date ON wms_receiving_details (item_code, received_date);
CREATE INDEX idx_wms_receiving_details_receiving_id ON wms_receiving_details (receiving_id);

CREATE INDEX idx_wms_receiving_receiving_status ON wms_receiving (receiving_id, status);

-- Optional (only if MySQL supports FULLTEXT and table engine allows it):
-- Improves contains-search workloads for item search.
-- ALTER TABLE wms_itemlist ADD FULLTEXT INDEX ftx_wms_itemlist_code_desc (item_code, item_description);
