<p>Good day {{ $application->name }},</p>

<p>LEEO reviewed your broker application and requested corrections before it can continue.</p>

@if($application->remarks)
    <p><strong>Review remarks:</strong> {{ $application->remarks }}</p>
@endif

<p>Please log in to the Maramag Fish Landing Management System, open your application, and upload the required revision documents.</p>

<p>Thank you.</p>
