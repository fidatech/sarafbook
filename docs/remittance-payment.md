# Pending remittance approval and payment

Open **Pending incoming remittances**, then use the green check action to open **Approve and pay remittance**. Review the transfer and receiver payout amount, attach the receiver's national ID copy and photo, confirm identity review, and submit. After successful payment, an 80 mm payment receipt opens in the same tab and invokes the browser print dialog. Confirm the printer in that dialog. The receipt uses the actual receiver payout amount and payment time/operator. The payment record and paid-remittance printer action allow reprinting without creating additional ledger entries. JSON responses include `receipt_url`. A payment record with document links remains available on the paid-remittances list.

- Uses the existing `update remittances` permission for payment, payment records and evidence access.
- ID copies accept JPEG, PNG, WebP or PDF; receiver photos accept JPEG, PNG or WebP. Both files are required and limited to 5 MB. ID number is optional.
- Fingerprint collection is displayed but disabled, not required, and rejected by the payment endpoint if submitted. Previously saved fingerprint evidence remains available to authorized users. Receiver photos can be captured using a webcam or phone camera, or uploaded. The local editor supports portrait cropping by dragging/zooming, rotation, brightness and contrast, then attaches the edited JPEG to the payment. Live camera access needs browser permission and HTTPS or localhost; a native phone camera/file picker remains available if live camera access fails. Camera tracks stop after capture, on cancellation and on leaving the page.
- Files are stored on the private `local` disk under `remittance-payments/{id}`. They are served through authenticated, permission-checked routes with no-store headers. Include private storage in backups alongside the database.
- Payment locks the remittance row and accepts only pending incoming `no_track` records. Both old payment endpoints use the same validated flow. Status, operator, time, audit event and the existing two payout ledger entries commit together. Failed transactions remove any files they stored.
- Verified payment records cannot be edited, rejected or deleted through remittance actions. This prevents ordinary edits from resetting a paid remittance to pending or detaching its evidence from the recorded payout. Legacy remittances without verified payment metadata retain their existing behavior.
- No new external scripts or services are required. English, Dari and Pashto translations are included.

For another installation, deploy the code and run:

```sh
php artisan migrate --path=database/migrations/2026_09_19_170000_add_payment_evidence_to_remittances.php --force
php artisan route:clear
php artisan view:clear
php artisan view:cache
```

The web server/PHP upload limits must permit two 5 MB attachments in one request. This checkout's CLI PHP limits are 256 MB; hosting settings may differ.

Regression checks:

```sh
php artisan test --compact --filter=RemittancePaymentTest
```
