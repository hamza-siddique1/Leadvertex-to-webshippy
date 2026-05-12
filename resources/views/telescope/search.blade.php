@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <h1 class="h3 mb-3">{{ __('Debug Screen') }}</h1>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Search Form -->
                    <form method="GET" action="{{ url()->current() }}">
                        <div class="row">
                            <!-- Request Method Dropdown -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="method">{{ __('Request Method') }}</label>
                                    <select id="method" name="method" class="form-control">
                                        <option value="" disabled {{ request('method') ? '' : 'selected' }}>
                                            {{ __('Select Request Method') }}
                                        </option>
                                        <option value="GET" {{ request('method') == 'GET' ? 'selected' : '' }}>GET</option>
                                        <option value="POST" {{ request('method') == 'POST' ? 'selected' : '' }}>POST</option>
                                        <option value="PUT" {{ request('method') == 'PUT' ? 'selected' : '' }}>PUT</option>
                                        <option value="PATCH" {{ request('method') == 'PATCH' ? 'selected' : '' }}>PATCH</option>
                                        <option value="DELETE" {{ request('method') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Response Status Input -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="resposne_status">{{ __('Response Status') }}</label>
                                    <input type="number" id="resposne_status" name="resposne_status" class="form-control"
                                           placeholder="{{ __('Enter Response Status') }}"
                                           value="{{ request('resposne_status') }}">
                                </div>
                            </div>

                            <!-- URI Input -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="uri">{{ __('URI') }}</label>
                                    <input type="text" id="uri" name="uri" class="form-control"
                                           placeholder="{{ __('Enter URI') }}"
                                           value="{{ request('uri') }}">
                                </div>
                            </div>

                            <!-- Search Term -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="uri">{{ __('Search') }}</label>
                                    <input type="text" id="search" name="search" class="form-control"
                                           placeholder="{{ __('Enter Search') }}"
                                           value="{{ request('search') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-lg btn-primary">{{ __('Search') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Type') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($results as $entry)
                                    <tr>
                                        <td>
                                            <a href="telescope/{{ $entry->type === 'client_request' ? 'client-requests' : 'requests' }}/{{ $entry->uuid }}" target="_blank">
                                                {{ $entry->uuid }}
                                            </a>
                                        </td>

                                        <td>
                                            {{ $entry->type }}
                                        </td>
                                    </tr>
                            @empty
                                <tr>
                                    <td colspan="1" class="text-center">{{ __('No results found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('telescope.api-log')

@endsection
