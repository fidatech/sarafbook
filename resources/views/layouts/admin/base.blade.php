{{--
    Layout of every page of a Sarafi:
    @extends('layouts.admin.base')  @section('title', ...)  @section('css') @section('content') @section('js')
    A page rebuilt in the Fida Sarafi design adds @section('modern', '1'): it then loads no jQuery,
    SweetAlert or old icon font, only the template's own script.
--}}
@include('layouts.shell', ['shell' => 'tenant', 'legacy' => ! $__env->hasSection('modern')])
