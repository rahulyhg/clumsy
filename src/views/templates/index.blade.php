@extends('clumsy::templates.master')

@section('page-header')
@stop

@section('master')

    @include($view->resolve('inner-index'))

@stop