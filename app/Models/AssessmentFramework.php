<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentFramework extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'is_active'];

    public function pillars()
    {
        return $this->hasMany(AssessmentPillar::class, 'framework_id');
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestionBank::class, 'framework_id');
    }
}
