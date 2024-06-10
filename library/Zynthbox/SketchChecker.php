<?php
/**
 * file server - part of Opendesktop.org platform project <https://www.opendesktop.org>.
 *
 * Copyright (c) 2016 pling GmbH.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Zynthbox;

use finfo;

class SketchChecker
{
    public function validate($fileTempPath, $fileName, $id3Tags) {
        return $this->testIsSketch($fileTempPath, $fileName, $id3Tags);
    }

    private function testIsSketch($fileTmpPath, $fileName, $id3Tags) {
        $resultData = $this->testFileExists($fileTmpPath);
        if (!$resultData['isValid']) {
            return $resultData;
        }
        $resultData = $this->testFileName($fileName);
        if (!$resultData['isValid']) {
            return $resultData;
        }
        $resultData = $this->testIsWaveFile($fileTmpPath);
        if (!$resultData['isValid']) {
            return $resultData;
        }
        if (isset($id3Tags['error'])) {
            return [
                "isValid"     => false,
                "errorString" => "Could not open file to read the tags: " . implode(', ', $id3Tags['error'])
            ];
        }

        return $this->validateZynthboxTags($id3Tags);
    }

    private function testFileExists($fileTmpPath) {
        if (!file_exists($fileTmpPath)) {
            return ["isValid" => false, "errorString" => "The file does not exist"];
        }

        return ["isValid" => true];
    }

    private function testFileName($fileName) {
        if (substr($fileName, -10) !== "sketch.wav") {
            return ["isValid" => false, "errorString" => "The file does not have the expected suffix .sketch.wav"];
        }

        return ["isValid" => true];
    }

    private function testIsWaveFile($fileTmpPath) {
        $info = new finfo();
        $mime = $info->file($fileTmpPath, FILEINFO_MIME_TYPE);
        if ($mime != "audio/x-wav") {
            return [
                "isValid"     => false,
                "errorString" => "Not a wave audio file (and definitely not a Zynthbox Sketch)"
            ];
        }

        return ["isValid" => true];
    }

    private function validateZynthboxTags($tagFile) {
        $requiredTags = [
            "ZYNTHBOX_BPM",
            "ZYNTHBOX_PATTERN_JSON",
            //"ZYNTHBOX_TRACK_AUDIOTYPESETTINGS",
            "ZYNTHBOX_ROUTING_STYLE",
            "ZYNTHBOX_ACTIVELAYER",
            //"ZYNTHBOX_TRACK_TYPE"
        ];

        $id3v2Tags = $tagFile['tags']['id3v2']['text'];
        foreach ($requiredTags as $tag) {
            if (!isset($id3v2Tags[$tag])) {
                return [
                    "isValid"     => false,
                    "errorString" => "This wave file does not contain the $tag tag required to be recognized as a Zynthbox Sketch"
                ];
            }
        }

        $suggestedTags = [
            "media##duration={$tagFile['playtime_seconds']}",
            "audio##samplerate={$tagFile['audio']['sample_rate']}",
            "audio##channels={$tagFile['audio']['channels']}"
        ];

        $bpm = $id3v2Tags["ZYNTHBOX_BPM"];
        if (isset($bpm)) {
            $suggestedTags[] = "music##bpm=$bpm";
        }

        $patternJson = $id3v2Tags["ZYNTHBOX_PATTERN_JSON"];
        if (isset($patternJson)) {
            $patternData = json_decode($patternJson, true);
            $octaveValues = [
                "octavenegative1" => 0,
                "octave0"         => 12,
                "octave1"         => 24,
                "octave2"         => 36,
                "octave3"         => 48,
                "octave4"         => 60,
                "octave5"         => 72,
                "octave6"         => 84,
                "octave7"         => 96,
                "octave8"         => 108,
                "octave9"         => 120
            ];
            $pitchValues = [
                "c"      => 0,
                "csharp" => 1,
                "dflat"  => 1,
                "d"      => 2,
                "dsharp" => 3,
                "eflat"  => 3,
                "e"      => 4,
                "f"      => 5,
                "fsharp" => 6,
                "gflat"  => 6,
                "g"      => 7,
                "gsharp" => 8,
                "aflat"  => 8,
                "a"      => 9,
                "asharp" => 10,
                "bflat"  => 10,
                "b"      => 11
            ];
            $octave = $octaveValues[$patternData["octave"]] ?? 0;
            $pitch = $pitchValues[$patternData["pitch"]] ?? 0;
            $rootKey = $octave + $pitch;
            $suggestedTags[] = "music##rootkey={$rootKey}";
            $suggestedTags[] = "music##rootpitch={$patternData['pitch']}";
            $suggestedTags[] = "music##scale={$patternData['scale']}";
        }

        $audioTypeSettings = $id3v2Tags["ZYNTHBOX_TRACK_AUDIOTYPESETTINGS"];
        if (isset($audioTypeSettings)) {
            $audioType = "synth";
            if (strpos($audioTypeSettings[0], "sample-") === 0 || $audioTypeSettings[0] == "external") {
                $audioType = "sample";
            }
            $suggestedTags[] = "zynthbox##audiotype={$audioType}";
        }

        return ["isValid" => true, "suggestedTags" => $suggestedTags];
    }

}
