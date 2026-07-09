<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>PawaPay Payment</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <style>
        body {
            font-size: 14px;
            font-family: "Moderat", "Inter", sans-serif;
            font-weight: 400;
            color: #333;
        }

        .center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
    </style>
</head>

<body>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 mb-3">
                <div class="text-dark bg-light mb-3">
                    <div class="center">
                        <form action="{{ route('pawapay.initiate') }}" method="POST">
                            @csrf
                            <h1 class="mt-3">{{ $payment->currency }} {{ $payment->amount }}</h1><br>
                            <input type="hidden" class="form-control" name="transaction_id" id="transaction_id"
                                value="{{ $payment->id }}">

                            @if($payment->payment_for=="wallet")
                            <button class="w-100 btn btn-lg btn-primary" type="submit">Add To Wallet</button>
                            @else
                            <button class="w-100 btn btn-lg btn-success" type="submit">Pay Now</button>
                            @endif
                        </form>

                        @if ($errors->any())
                        <div class="alert alert-danger text-start" role="alert">
                            <strong>Opps!</strong> Something went wrong<br>
                            <ul>
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

