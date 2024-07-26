@extends('errors::illustrated-layout')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('Authorization Required. You don't have access to this page or resource.'))