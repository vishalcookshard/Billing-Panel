@extends('app')

@section('title', $page->title . ' - ' . config('app.name'))
@section('meta_description', $page->meta_description ?? Str::limit(strip_tags($page->content), 160))
@section('meta_keywords', $page->meta_keywords)

@section('content')
<!-- Breadcrumb -->
<div style="background-color: #f8f9fa; padding: 20px 0;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item active">{{ $page->title }}</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Page Content -->
<section>
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <article>
                    <h1>{{ $page->title }}</h1>
                    <hr>
                    {{-- Safe: Content sanitized by HTMLPurifier in Page model --}}
                    <div class="page-content" style="line-height: 1.8; color: #555;">
                        {!! $page->content !!}
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>
@endsection
