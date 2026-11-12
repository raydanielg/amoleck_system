<!DOCTYPE html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Secure Document Access</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh">
<div class="container"><div class="row justify-content-center"><div class="col-md-4">
    <div class="card shadow-sm border-0"><div class="card-body p-4 text-center">
        <h6 class="mb-3">Protected Document</h6>
        <p class="small text-muted">This document is password protected. Enter the password to continue.</p>
        <form method="POST" action="{{ route('documents.link.access',$token) }}">@csrf
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required autofocus>
            @if ($errors->any() || session('error'))<div class="alert alert-danger small py-1">Incorrect password.</div>@endif
            <button class="btn btn-primary w-100 btn-arrow"><span class="btn-arrow-label">Access document</span><span class="btn-arrow-tile">→</span></button>
        </form>
    </div></div>
</div></div></div>
</body></html>
