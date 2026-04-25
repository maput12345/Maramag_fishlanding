<p>Good day {{ $application->name }},</p>

<p>Your application for {{ $stall->display_name }} at Maramag Fish Landing has been qualified for the stall bidding.</p>

<p><strong>Bidding Location:</strong> {{ $opening->bidding_location ?: 'Maramag Fish Landing - ' . $stall->display_name }}</p>
<p><strong>Bidding Start Date:</strong> {{ optional($opening->bidding_date ?? $opening->start_date)->format('F j, Y') ?? 'To be announced' }}</p>

<p>Please wait for any further instructions from the LEEO office regarding the bidding process.</p>

<p>Thank you.</p>
