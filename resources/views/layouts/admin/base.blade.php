{{--
    Layout of every page of a Sarafi:
    @extends('layouts.admin.base')  @section('title', ...)  @section('css') @section('content') @section('js')
--}}
@include('layouts.shell', ['shell' => 'tenant', 'legacy' => true])
