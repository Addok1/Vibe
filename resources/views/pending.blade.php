<style type="text/css">
	.container{
		display: inline-block;
		position: relative;
		top: 300px;
		left: 600px;
		align-content: center;
		align-items: center;
	}
	h3{
		text-align: center;
	}
</style>

<div class="container">
	<div class="row" style="align-items: center;align-content: center;">
		<div class="col-md-12">
			<img src="{{asset('assets/img/cross.png')}}" style="width:150px;margin-top:25px;margin-bottom:25px;" alt="">
			<h3 class="text-center text-success">Pending</h3>
			@if(!empty($status_url))
				<p class="text-center">Please approve the mobile money prompt on your phone.</p>
				<p id="payment-status-hint" class="text-center" style="opacity:.8;"></p>
			@endif
		</div>
	</div>
</div>

@if(!empty($status_url))
<script>
    const statusUrl = @json($status_url);
	const hintEl = document.getElementById('payment-status-hint');
    let attempts = 0;
    const maxAttempts = 60;

    const checkPaymentStatus = async () => {
        attempts += 1;

        try {
            const response = await fetch(statusUrl, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

			if (hintEl) {
				const remote = data.pawapay_status ? `PawaPay: ${data.pawapay_status}` : '';
				const err = data.error ? `Error: ${data.error}` : '';
				const raw = data.debug && data.debug.raw_body ? `Raw: ${data.debug.raw_body}` : '';
				hintEl.textContent = [remote, err, raw].filter(Boolean).join(' • ');
			}

            if ((data.status === 'success' || data.status === 'failed') && data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }
        } catch (error) {
            console.error('Payment status check failed', error);
        }

        if (attempts < maxAttempts) {
            setTimeout(checkPaymentStatus, 5000);
		} else if (hintEl) {
			hintEl.textContent = hintEl.textContent
				? `${hintEl.textContent} • Still pending. Please refresh after approving on your phone.`
				: 'Still pending. Please refresh after approving on your phone.';
        }
    };

    setTimeout(checkPaymentStatus, 5000);
</script>
@endif
