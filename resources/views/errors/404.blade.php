@extends('errors::minimal')

@section('title', __('general.404_title'))
@section('heading', 'No encontramos esa página')
@section('code', '404')
@section('img', asset('images/error/404.png'))
@section('message', 'Puede que el enlace haya cambiado o que la página ya no exista. Vuelve al inicio o explora nuestros cursos.')
