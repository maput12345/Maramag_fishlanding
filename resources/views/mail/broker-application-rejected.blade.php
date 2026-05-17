<p>Good day {{ $application->name }},</p>

<p>LEEO has completed the review of your broker application. We regret to inform you that the application was not approved.</p>

@if($application->remarks)
    <p><strong>Review remarks:</strong> {{ $application->remarks }}</p>
@endif

<p>You may log in to the Maramag Fish Landing Management System to view the application review details.</p>

<p>Thank you.</p>
