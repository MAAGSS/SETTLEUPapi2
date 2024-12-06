<!DOCTYPE html>
<html>
<head>
    <title>Payment Confirmation</title>
</head>
<body>
    <h1>Payment Confirmation</h1>
    <p>Dear {{ $debtorName }},</p>
    
    <p>This is to confirm that a payment has been processed:</p>
    
    <ul>
        <li>Payment Amount: ${{ number_format($paymentAmount, 2) }}</li>
        <li>Remaining Balance: ${{ number_format($remainingBalance, 2) }}</li>
        <li>Due Date: {{ $dueDate }}</li>
    </ul>
    
    <p>Thank you for your payment.</p>
</body>
</html>