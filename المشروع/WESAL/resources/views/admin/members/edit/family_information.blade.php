<div class="card-header bg-dark text-white">
    <h5 class="mb-0 h6">{{translate('Family Information')}}</h5>
</div>
<div class="card-body">
    <form action="{{ route('families.update', $member->id) }}#family_information" method="POST">
        <input name="_method" type="hidden" value="PATCH">
        @csrf
        <div class="form-group row">
            {{-- Father --}}
            <div class="col-md-6">
                <label for="father">{{translate('Father')}}</label>
                <input type="text" name="father" value="{{ $member->families->father ?? "" }}" class="form-control" placeholder="{{translate('Father')}}" required>
                @error('father')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="mother">{{translate('Father Occupation')}}</label>
                <input type="text" name="father_occupation" value="{{ $member->families->father_occupation ?? "" }}" placeholder="{{ translate('Father Occupation') }}" class="form-control" required>
                @error('father_occupation')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Mother --}}
            <div class="col-md-6 mt-3">
                <label for="mother">{{translate('Mother')}}</label>
                <input type="text" name="mother" value="{{ $member->families->mother ?? "" }}" placeholder="{{ translate('Mother') }}" class="form-control" required>
                @error('mother')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6 mt-3">
                <label for="mother">{{translate('Mother Occupation')}}</label>
                <input type="text" name="mother_occupation" value="{{ $member->families->mother_occupation ?? "" }}" placeholder="{{ translate('Mother Occupation') }}" class="form-control" required>
                @error('mother_occupation')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- sibling --}}
            {{-- <div class="col-md-6 mt-3">
                <label for="sibling">{{translate('Sibling')}}</label>
                <input type="text" name="sibling" value="{{ $member->families->sibling ?? "" }}" id="sibling" class="form-control" placeholder="{{translate('Sibling')}}" readonly >
                @error('sibling')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div> --}}
            <div class="col-md-6 mt-3">
                <label for="sibling">{{translate('No. of Brothers')}}</label>
                <select class="form-control aiz-selectpicker" name="no_of_brothers" id="no_of_brothers" onchange="totalSibling()" data-live-search="true" data-selected="{{ $member->families->no_of_brothers ?? ""  }}" required>
                    @for($i=0; $i<=20; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                @error('no_of_brothers')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6 mt-3">
                <label for="sibling">{{translate('No. of Sister')}}</label>
                <select class="form-control aiz-selectpicker" name="no_of_sisters" id="no_of_sisters" onchange="totalSibling()" data-live-search="true" data-selected="{{ $member->families->no_of_sisters ?? ""  }}" required>
                    @for($i=0; $i<=20; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                @error('no_of_sisters')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- About parents --}}
            <div class="col-md-12 mt-3">
                <label for="mother">{{translate('About Parents')}}</label>
                <textarea type="text" name="about_parents" value="{{ $member->families->about_parents ?? "" }}" rows="4" placeholder="{{ translate('About Parents') }}" class="form-control">{{ $member->families->about_parents ?? "" }}</textarea>
                @error('about_parents')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- About Siblings --}}
            <div class="col-md-12 mt-3">
                <label for="mother">{{translate('About Siblings')}}</label>
                <textarea type="text" name="about_siblings" value="{{ $member->families->about_siblings ?? "" }}" rows="4" placeholder="{{ translate('About Siblings') }}" class="form-control">{{ $member->families->about_siblings ?? "" }}</textarea>
                @error('about_siblings')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- About Relatives --}}
            <div class="col-md-12 mt-3">
                <label for="mother">{{translate('About Relatives')}}</label>
                <textarea type="text" name="about_relatives" value="{{ $member->families->about_relatives ?? "" }}" rows="4" placeholder="{{ translate('About Parents') }}" class="form-control">{{ $member->families->about_relatives ?? "" }}</textarea>
                @error('about_relatives')
                    <small class="form-text text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <div class="text-right">
            <button type="submit" class="btn btn-primary btn-sm">{{translate('Update')}}</button>
        </div>
    </form>
</div>
